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
            ->assertDontSee('/Forensic_Head/permissions', false)
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
            ->assertSee('Add Associate')
            ->assertSee('Lab &amp; Drug Screening', false)
            ->assertSee('Generate Reports &amp; PDFs', false)
            ->assertDontSee('Manage Exhibit Vault')
            ->assertDontSee('Digital Forensics Intake')
            ->assertDontSee('Notifications &amp; Handovers', false);

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
                'add_associate' => true,
                'record_lab_analysis' => true,
                'generate_reports' => false,
            ],
        ]);
        $updateResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('officer.enabled', 2)
            ->assertJsonPath('officer.total', 5);

        $freshStaff = $labStaff->fresh();
        $this->assertTrue($freshStaff->hasPermission('record_lab_analysis'));
        $this->assertTrue($freshStaff->hasPermission('add_associate'));
        $this->assertFalse($freshStaff->hasPermission('create_cases'));
        $this->assertFalse($freshStaff->hasPermission('edit_cases'));
        $this->assertFalse($freshStaff->hasPermission('generate_reports'));

        // 5. Restrict All preset sets every capability to false cleanly
        $restrictResp = $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'preset' => 'restrict_all',
        ]);
        $restrictResp->assertOk()
            ->assertJsonPath('officer.enabled', 0);
        $this->assertFalse($labStaff->fresh()->hasPermission('record_lab_analysis'));

        // 6. Grant All preset enables all 5 applicable capabilities
        $grantResp = $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'preset' => 'grant_all',
        ]);
        $grantResp->assertOk()
            ->assertJsonPath('officer.enabled', 5);
        $this->assertTrue($labStaff->fresh()->hasPermission('create_cases'));

        // 7. CSS Head can load CSS permissions page ONLY for their own unit staff and sees only the 4 streamlined permissions
        $this->actingAs($cssHead)->get('/Crime_Scene_Support/permissions')
            ->assertOk()
            ->assertSee('Ama Scene Officer')
            ->assertDontSee('Kofi Chemist')
            ->assertDontSee('View Case Directory')
            ->assertSee('Create New Cases')
            ->assertSee('Edit Case &amp; Suspects', false)
            ->assertSee('Add Associate')
            ->assertSee('Generate Reports &amp; PDFs', false)
            ->assertDontSee('Manage Exhibit Vault')
            ->assertDontSee('Lab &amp; Drug Screening', false)
            ->assertDontSee('Digital Forensics Intake')
            ->assertDontSee('Notifications &amp; Handovers', false)
            ->assertSee('4/4 active');

        // 8. Digital Forensics Head sees only the 4 streamlined permissions on /Digital_Forensics/permissions
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
            ->assertSee('Add Associate')
            ->assertSee('Generate Reports &amp; PDFs', false)
            ->assertDontSee('Manage Exhibit Vault')
            ->assertDontSee('Lab &amp; Drug Screening', false)
            ->assertDontSee('Digital Forensics Intake')
            ->assertDontSee('Notifications &amp; Handovers', false)
            ->assertSee('4/4 active');
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

    #[Test]
    public function head_toggling_edit_cases_permission_controls_staff_review_edit_button_and_edit_routes(): void
    {
        $head = User::factory()->create([
            'fullname' => 'CSSU Head Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staff = User::factory()->create([
            'fullname' => 'CSSU Field Examiner',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
            'permissions' => null,
        ]);

        $case = \App\Models\ForensicCase::create([
            'case_number' => 'CAS-CSS-EDIT-001',
            'title' => 'Warehouse Seizure Documentation',
            'unit_name' => 'Crime Scene Support',
            'submitting_officer' => $staff->fullname,
            'created_by' => $staff->id,
            'suspect_name' => 'Kojo Mensah',
            'status' => 'Active',
            'priority' => 'HIGH',
        ]);

        $staffReviewUrl = "/Crime_Scene_Support/staff/cases/{$case->case_number}/review";
        $editHref = "/cases/{$case->case_number}/edit?from=Crime_Scene_Support";

        // Default state: Edit Case/Update Case Records is enabled
        $this->actingAs($staff)
            ->get($staffReviewUrl)
            ->assertOk()
            ->assertSee('Edit Case/Update Case Records')
            ->assertSee('data-staff-edit-btn="review"', false)
            ->assertSee('aria-disabled="false"', false)
            ->assertSee("href=\"{$editHref}\"", false);

        $this->actingAs($staff)
            ->getJson("/api/auth/account-status?path={$staffReviewUrl}")
            ->assertOk()
            ->assertJsonPath('can_edit_cases', true);

        $this->actingAs($staff)
            ->get($editHref)
            ->assertOk();

        // Unit Head toggles OFF "Edit Case & Suspects" for this staff officer
        $this->actingAs($head)
            ->postJson("/permissions/{$staff->id}/update", [
                'permissions' => [
                    'create_cases' => true,
                    'edit_cases' => false,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('officer.permissions.edit_cases', false);

        // Staff review page now locks the Edit Case/Update Case Records button
        $lockedHtml = $this->actingAs($staff->fresh())
            ->get($staffReviewUrl)
            ->assertOk()
            ->assertSee('Edit Case/Update Case Records')
            ->assertSee('data-staff-edit-btn="review"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('Locked: Edit Case &amp; Suspects permission is disabled by your Head of Unit', false)
            ->assertDontSee("href=\"{$editHref}\"", false);

        $this->assertNotEmpty($lockedHtml->getContent());

        // Unit show_review & generic case review also lock the button
        $this->actingAs($staff->fresh())
            ->get("/Crime_Scene_Support/cases/{$case->case_number}/review")
            ->assertOk()
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee("href=\"{$editHref}\"", false);

        $this->actingAs($staff->fresh())
            ->get("/cases/{$case->case_number}/review")
            ->assertOk()
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee("href=\"/cases/{$case->case_number}/edit\"", false);

        $this->actingAs($staff->fresh())
            ->getJson("/api/auth/account-status?path={$staffReviewUrl}")
            ->assertOk()
            ->assertJsonPath('can_edit_cases', false);

        // Direct GET edit and POST update are blocked while disabled
        $this->actingAs($staff->fresh())
            ->get($editHref)
            ->assertRedirect('/Crime_Scene_Support/staff/case')
            ->assertSessionHas('error');

        $this->actingAs($staff->fresh())
            ->get("/Crime_Scene_Support/cases/{$case->case_number}/edit")
            ->assertRedirect('/Crime_Scene_Support/staff/case')
            ->assertSessionHas('error');

        $this->actingAs($staff->fresh())
            ->postJson("/cases/{$case->case_number}/update", [
                'from' => 'Crime_Scene_Support',
                'suspect_name' => 'Tampered Name',
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('permission_denied', 'edit_cases');

        // Unit Head toggles ON "Edit Case & Suspects" for the selected staff officer
        $this->actingAs($head)
            ->postJson("/permissions/{$staff->id}/update", [
                'permissions' => [
                    'create_cases' => true,
                    'edit_cases' => true,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('officer.permissions.edit_cases', true);

        // Staff review page immediately re-enables the Edit Case/Update Case Records button
        $this->actingAs($staff->fresh())
            ->get($staffReviewUrl)
            ->assertOk()
            ->assertSee('Edit Case/Update Case Records')
            ->assertSee('aria-disabled="false"', false)
            ->assertSee("href=\"{$editHref}\"", false)
            ->assertDontSee('data-locked-tooltip="Locked: Edit Case &amp; Suspects', false);

        $this->actingAs($staff->fresh())
            ->getJson("/api/auth/account-status?path={$staffReviewUrl}")
            ->assertOk()
            ->assertJsonPath('can_edit_cases', true);

        $this->actingAs($staff->fresh())
            ->get($editHref)
            ->assertOk();
    }

    #[Test]
    public function edit_cases_permission_toggles_cleanly_across_all_units_with_unit_isolation(): void
    {
        $units = [
            ['unit' => 'Crime Scene Support', 'slug' => 'Crime_Scene_Support'],
            ['unit' => 'Lab Analysis', 'slug' => 'Lab_Analysis'],
            ['unit' => 'Evidence Management', 'slug' => 'Evidence_Management'],
            ['unit' => 'Digital & Physical Forensics', 'slug' => 'Digital_Forensics'],
        ];

        $otherUnitStaff = User::factory()->create([
            'fullname' => 'Observer CSSU Staff',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
            'permissions' => null,
        ]);

        foreach ($units as $idx => $cfg) {
            $head = User::factory()->create([
                'fullname' => "{$cfg['slug']} Head",
                'unit' => $cfg['unit'],
                'is_head' => true,
                'is_approved' => true,
            ]);

            $staff = User::factory()->create([
                'fullname' => "{$cfg['slug']} Staff",
                'unit' => $cfg['unit'],
                'is_head' => false,
                'is_approved' => true,
                'permissions' => null,
            ]);

            $case = \App\Models\ForensicCase::create([
                'case_number' => "CAS-UNIT-EDIT-{$idx}",
                'title' => "{$cfg['unit']} Exhibit Review",
                'unit_name' => $cfg['unit'],
                'submitting_officer' => $staff->fullname,
                'created_by' => $staff->id,
                'suspect_name' => 'Abena Osei',
                'status' => 'Under Analysis',
                'priority' => 'HIGH',
            ]);

            $reviewUrl = "/{$cfg['slug']}/staff/cases/{$case->case_number}/review";
            $editHref = "/cases/{$case->case_number}/edit?from={$cfg['slug']}";

            // Disable edit_cases via unit head
            $this->actingAs($head)
                ->postJson("/permissions/{$staff->id}/update", [
                    'permissions' => [
                        'create_cases' => true,
                        'edit_cases' => false,
                    ],
                ])
                ->assertOk();

            $this->actingAs($staff->fresh())
                ->get($reviewUrl)
                ->assertOk()
                ->assertSee('aria-disabled="true"', false)
                ->assertDontSee("href=\"{$editHref}\"", false);

            // Unit Head themselves is never restricted
            $this->actingAs($head)
                ->get($reviewUrl)
                ->assertOk()
                ->assertSee('aria-disabled="false"', false)
                ->assertSee("href=\"{$editHref}\"", false);

            // Re-enable edit_cases via unit head
            $this->actingAs($head)
                ->postJson("/permissions/{$staff->id}/update", [
                    'permissions' => [
                        'create_cases' => true,
                        'edit_cases' => true,
                    ],
                ])
                ->assertOk();

            $this->actingAs($staff->fresh())
                ->get($reviewUrl)
                ->assertOk()
                ->assertSee('aria-disabled="false"', false)
                ->assertSee("href=\"{$editHref}\"", false);
        }

        // Cross-unit isolation check: disabling edit_cases on a DPF officer must not lock CSSU review button
        $dpfHead = User::factory()->create([
            'fullname' => 'Isolated DPF Head',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => true,
            'is_approved' => true,
        ]);
        $dpfStaff = User::factory()->create([
            'fullname' => 'Isolated DPF Staff',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);
        $cssCase = \App\Models\ForensicCase::where('case_number', 'CAS-UNIT-EDIT-0')->first();
        $this->assertNotNull($cssCase);

        $this->actingAs($dpfHead)
            ->postJson("/permissions/{$dpfStaff->id}/update", [
                'permissions' => [
                    'create_cases' => true,
                    'edit_cases' => false,
                ],
            ])
            ->assertOk();

        $this->actingAs($otherUnitStaff->fresh())
            ->get("/Crime_Scene_Support/staff/cases/{$cssCase->case_number}/review")
            ->assertOk()
            ->assertSee('aria-disabled="false"', false)
            ->assertSee("href=\"/cases/{$cssCase->case_number}/edit?from=Crime_Scene_Support\"", false);

        $this->actingAs($dpfStaff->fresh())
            ->getJson("/api/auth/account-status?path=/Crime_Scene_Support/staff/cases/{$cssCase->case_number}/review")
            ->assertOk()
            ->assertJsonPath('can_edit_cases', true);
    }

    #[Test]
    public function head_toggling_add_associate_permission_controls_staff_case_page_action_and_associate_routes(): void
    {
        $head = User::factory()->create([
            'fullname' => 'CSSU Head Commander',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staff = StaffAccount::create([
            'fullname' => 'CSSU Case Officer',
            'username' => 'cssu_assoc_officer',
            'service_id' => 'SD-991',
            'phone_number' => '0244000991',
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'permissions' => null,
            'password' => bcrypt('password'),
        ]);

        $case = \App\Models\ForensicCase::create([
            'case_number' => 'CAS-CSS-ASSOC-001',
            'title' => 'Border Convoy Interception',
            'unit_name' => 'Crime Scene Support',
            'submitting_officer' => $staff->fullname,
            'suspect_name' => 'Kwadwo Owusu',
            'status' => 'Active',
            'priority' => 'HIGH',
        ]);

        $staffCasePage = '/Crime_Scene_Support/staff/case';
        $associateHref = "/cases/{$case->case_number}/associate?from=" . urlencode('Crime Scene Support');

        // Permissions console displays Add Associate toggle for the selected staff member
        $this->actingAs($head)
            ->get('/Crime_Scene_Support/permissions')
            ->assertOk()
            ->assertSee('Add Associate')
            ->assertSee('data-perm-key="add_associate"', false);

        // Default state: Add Associate is enabled on the staff case page & associate route works
        $this->actingAs($staff)
            ->get($staffCasePage)
            ->assertOk()
            ->assertSee('Add Associate')
            ->assertSee('data-staff-associate-btn="menu"', false)
            ->assertSee('aria-disabled="false"', false)
            ->assertSee("href=\"{$associateHref}\"", false);

        $this->actingAs($staff)
            ->getJson("/api/auth/account-status?path={$staffCasePage}")
            ->assertOk()
            ->assertJsonPath('can_add_associate', true);

        $this->actingAs($staff)
            ->get($associateHref)
            ->assertOk();

        // Head toggles OFF Add Associate for the selected staff officer
        $this->actingAs($head)
            ->postJson("/permissions/{$staff->id}/update", [
                'permissions' => [
                    'create_cases' => true,
                    'edit_cases' => true,
                    'add_associate' => false,
                    'generate_reports' => true,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('officer.permissions.add_associate', false);

        // Staff case page action menu & generic staff review now lock Add Associate
        $this->actingAs($staff->fresh())
            ->get($staffCasePage)
            ->assertOk()
            ->assertSee('Add Associate')
            ->assertSee('data-staff-associate-btn="menu"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('Locked: Add Associate permission is disabled by your Head of Unit', false)
            ->assertDontSee("href=\"{$associateHref}\"", false);

        $this->actingAs($staff->fresh())
            ->get('/Forensic_Head/staff/case')
            ->assertOk()
            ->assertSee('data-staff-associate-btn="menu"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee("href=\"/cases/{$case->case_number}/associate", false);

        $this->actingAs($staff->fresh())
            ->get('/staff/case')
            ->assertOk()
            ->assertSee('data-staff-associate-btn="menu"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee("href=\"/cases/{$case->case_number}/associate", false);

        $this->actingAs($staff->fresh())
            ->getJson("/api/auth/account-status?path={$staffCasePage}")
            ->assertOk()
            ->assertJsonPath('can_add_associate', false);

        // Direct GET & POST /associate requests are blocked while disabled
        $this->actingAs($staff->fresh())
            ->get($associateHref)
            ->assertRedirect('/Crime_Scene_Support/staff/case')
            ->assertSessionHas('error');

        $this->actingAs($staff->fresh())
            ->get("/Crime_Scene_Support/cases/{$case->case_number}/associate")
            ->assertRedirect('/Crime_Scene_Support/staff/case')
            ->assertSessionHas('error');

        $this->actingAs($staff->fresh())
            ->postJson("/cases/{$case->case_number}/associate", [
                'from' => 'Crime_Scene_Support',
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('permission_denied', 'add_associate');

        // Head toggles ON Add Associate for the selected staff officer
        $this->actingAs($head)
            ->postJson("/permissions/{$staff->id}/update", [
                'permissions' => [
                    'create_cases' => true,
                    'edit_cases' => true,
                    'add_associate' => true,
                    'generate_reports' => true,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('officer.permissions.add_associate', true);

        // Staff case page immediately re-enables Add Associate
        $this->actingAs($staff->fresh())
            ->get($staffCasePage)
            ->assertOk()
            ->assertSee('Add Associate')
            ->assertSee("href=\"{$associateHref}\"", false)
            ->assertDontSee('data-locked-tooltip="Locked: Add Associate permission', false);

        $this->actingAs($staff->fresh())
            ->getJson("/api/auth/account-status?path={$staffCasePage}")
            ->assertOk()
            ->assertJsonPath('can_add_associate', true);

        $this->actingAs($staff->fresh())
            ->get($associateHref)
            ->assertOk();
    }

    #[Test]
    public function add_associate_permission_toggles_cleanly_across_all_units_with_unit_isolation(): void
    {
        $units = [
            ['unit' => 'Crime Scene Support', 'slug' => 'Crime_Scene_Support', 'sidebar' => 'Crime Scene Support'],
            ['unit' => 'Lab Analysis', 'slug' => 'Lab_Analysis', 'sidebar' => 'Lab Analysis'],
            ['unit' => 'Evidence Management', 'slug' => 'Evidence_Management', 'sidebar' => 'Evidence Management'],
            ['unit' => 'Digital & Physical Forensics', 'slug' => 'Digital_Forensics', 'sidebar' => 'Digital Forensics'],
        ];

        $cssObserver = User::factory()->create([
            'fullname' => 'CSSU Observer Officer',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
            'permissions' => null,
        ]);

        foreach ($units as $idx => $cfg) {
            $head = User::factory()->create([
                'fullname' => "{$cfg['slug']} Assoc Head",
                'unit' => $cfg['unit'],
                'is_head' => true,
                'is_approved' => true,
            ]);

            $staff = User::factory()->create([
                'fullname' => "{$cfg['slug']} Assoc Staff",
                'unit' => $cfg['unit'],
                'is_head' => false,
                'is_approved' => true,
                'permissions' => null,
            ]);

            $case = \App\Models\ForensicCase::create([
                'case_number' => "CAS-UNIT-ASSOC-{$idx}",
                'title' => "{$cfg['unit']} Multi-Suspect Intake",
                'unit_name' => $cfg['unit'],
                'submitting_officer' => $staff->fullname,
                'created_by' => $staff->id,
                'suspect_name' => 'Yaw Boateng',
                'status' => 'Active',
                'priority' => 'HIGH',
            ]);

            $casePageUrl = "/{$cfg['slug']}/staff/case";
            $associateHref = "/cases/{$case->case_number}/associate?from=" . urlencode($cfg['sidebar']);

            // Toggle OFF add_associate for unit staff
            $this->actingAs($head)
                ->postJson("/permissions/{$staff->id}/update", [
                    'permissions' => [
                        'create_cases' => true,
                        'edit_cases' => true,
                        'add_associate' => false,
                        'generate_reports' => true,
                    ],
                ])
                ->assertOk();

            $this->actingAs($staff->fresh())
                ->get($casePageUrl)
                ->assertOk()
                ->assertSee('data-staff-associate-btn="menu"', false)
                ->assertSee('data-locked-tooltip="Locked: Add Associate permission is disabled by your Head of Unit"', false)
                ->assertDontSee("href=\"{$associateHref}\"", false);

            // Toggle ON add_associate for unit staff
            $this->actingAs($head)
                ->postJson("/permissions/{$staff->id}/update", [
                    'permissions' => [
                        'create_cases' => true,
                        'edit_cases' => true,
                        'add_associate' => true,
                        'generate_reports' => true,
                    ],
                ])
                ->assertOk();

            $this->actingAs($staff->fresh())
                ->get($casePageUrl)
                ->assertOk()
                ->assertSee("href=\"{$associateHref}\"", false)
                ->assertDontSee('data-locked-tooltip="Locked: Add Associate permission', false);
        }

        // Cross-unit isolation check: disabling DPF staff's add_associate leaves CSSU staff & CSSU page unlocked
        $dpfHead = User::factory()->create([
            'fullname' => 'Isolated Assoc DPF Head',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => true,
            'is_approved' => true,
        ]);
        $dpfStaff = User::factory()->create([
            'fullname' => 'Isolated Assoc DPF Staff',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);
        $cssCase = \App\Models\ForensicCase::where('case_number', 'CAS-UNIT-ASSOC-0')->first();
        $this->assertNotNull($cssCase);

        $this->actingAs($dpfHead)
            ->postJson("/permissions/{$dpfStaff->id}/update", [
                'permissions' => [
                    'create_cases' => true,
                    'edit_cases' => true,
                    'add_associate' => false,
                    'generate_reports' => true,
                ],
            ])
            ->assertOk();

        $this->actingAs($cssObserver->fresh())
            ->get('/Crime_Scene_Support/staff/case')
            ->assertOk()
            ->assertSee("href=\"/cases/{$cssCase->case_number}/associate?from=" . urlencode('Crime Scene Support') . '"', false)
            ->assertDontSee('data-locked-tooltip="Locked: Add Associate permission', false);

        $this->actingAs($dpfStaff->fresh())
            ->getJson('/api/auth/account-status?path=/Crime_Scene_Support/staff/case')
            ->assertOk()
            ->assertJsonPath('can_add_associate', true);
    }

    #[Test]
    public function lab_head_permissions_page_excludes_vault_digital_and_notifications_toggles(): void
    {
        $labHead = User::factory()->head()->create([
            'fullname' => 'Dr. Kwame Lab Head',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $labStaff = StaffAccount::create([
            'fullname' => 'Akosua Analyst',
            'username' => 'akosua_analyst',
            'service_id' => 'LAU-101',
            'phone_number' => '0244111222',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // Verify definitions for unit
        $permDefs = User::permissionsForUnit('Lab Analysis', '/Lab_Analysis/permissions');
        $this->assertCount(5, $permDefs);
        $this->assertEquals([
            'create_cases',
            'edit_cases',
            'add_associate',
            'record_lab_analysis',
            'generate_reports',
        ], array_keys($permDefs));

        // GET Lab Head permissions console
        $resp = $this->actingAs($labHead)->get('/Lab_Analysis/permissions');
        $resp->assertOk()
            ->assertSee('Akosua Analyst')
            ->assertSee('Create New Cases')
            ->assertSee('Edit Case &amp; Suspects', false)
            ->assertSee('Add Associate')
            ->assertSee('Lab &amp; Drug Screening', false)
            ->assertSee('Generate Reports &amp; PDFs', false)
            // Explicitly verify removed toggles are not present
            ->assertDontSee('Manage Exhibit Vault')
            ->assertDontSee('Digital Forensics Intake')
            ->assertDontSee('Notifications &amp; Handovers', false)
            ->assertSee('5/5 active');

        // Verify AJAX poll table partial also respects exclusion
        $ajaxResp = $this->actingAs($labHead)->get('/Lab_Analysis/permissions?poll=1');
        $ajaxResp->assertOk();
        $html = $ajaxResp->json('html');
        $this->assertStringContainsString('Lab &amp; Drug Screening', $html);
        $this->assertStringNotContainsString('Manage Exhibit Vault', $html);
        $this->assertStringNotContainsString('Digital Forensics Intake', $html);
        $this->assertStringNotContainsString('Notifications &amp; Handovers', $html);
    }

    #[Test]
    public function lab_head_toggling_off_drug_screening_locks_drug_screening_button_and_blocks_routes(): void
    {
        $labHead = User::factory()->head()->create([
            'fullname' => 'Dr. Chemist Lab Head',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $labStaff = StaffAccount::create([
            'fullname' => 'Kojo Lab Officer',
            'username' => 'kojo_lab',
            'service_id' => 'LAU-201',
            'phone_number' => '0244111333',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // 1. Initially enabled staff can view unlocked Drug Screening button
        $initPage = $this->actingAs($labStaff)->get('/Lab_Analysis/staff/case');
        $initPage->assertOk()
            ->assertSee('data-staff-drug-screening-btn="header"', false)
            ->assertSee('data-staff-drug-screening-btn="sidebar"', false)
            ->assertSee('href="' . url('/Lab_Analysis/staff/drug-screening') . '"', false)
            ->assertDontSee('data-locked-tooltip="Locked: Lab &amp; Drug Screening permission', false);

        // 2. Lab Head toggles OFF record_lab_analysis
        $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'permissions' => [
                'create_cases' => true,
                'edit_cases' => true,
                'add_associate' => true,
                'record_lab_analysis' => false,
                'generate_reports' => true,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // 3. Drug Screening button is locked with tooltip on staff case page and sidebar
        $casePage = $this->actingAs($labStaff->fresh())->get('/Lab_Analysis/staff/case');
        $casePage->assertOk()
            ->assertSee('data-staff-drug-screening-btn="header"', false)
            ->assertSee('data-staff-drug-screening-btn="sidebar"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('data-locked-tooltip="Locked: Lab &amp; Drug Screening permission is disabled by your Head of Unit"', false)
            ->assertSee('title="Locked: Lab &amp; Drug Screening permission is disabled by your Head of Unit"', false);

        // 4. Live account-status watchdog reports can_record_lab_analysis = false
        $this->actingAs($labStaff->fresh())
            ->getJson('/api/auth/account-status?path=/Lab_Analysis/staff/case')
            ->assertOk()
            ->assertJsonPath('can_record_lab_analysis', false);

        // 5. Direct access to drug screening routes is denied and redirected
        $this->actingAs($labStaff->fresh())
            ->get('/Lab_Analysis/staff/drug-screening')
            ->assertRedirect('/Lab_Analysis/staff/case');

        $this->actingAs($labStaff->fresh())
            ->get('/Lab_Analysis/staff/drug-screening/create')
            ->assertRedirect('/Lab_Analysis/staff/case');
    }

    #[Test]
    public function lab_head_toggling_off_generate_reports_locks_sitrep_reports_button_and_blocks_routes(): void
    {
        $labHead = User::factory()->head()->create([
            'fullname' => 'Dr. Chemist Lab Head',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $labStaff = StaffAccount::create([
            'fullname' => 'Ama Lab Officer',
            'username' => 'ama_lab',
            'service_id' => 'LAU-202',
            'phone_number' => '0244111444',
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // 1. Initially enabled staff can view unlocked Sitrep Reports button
        $initPage = $this->actingAs($labStaff)->get('/Lab_Analysis/staff/case');
        $initPage->assertOk()
            ->assertSee('data-staff-sitrep-btn="header"', false)
            ->assertSee('data-staff-sitrep-btn="sidebar"', false)
            ->assertSee('href="' . url('/Lab_Analysis/staff/report') . '"', false);

        // 2. Lab Head toggles OFF generate_reports
        $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'permissions' => [
                'create_cases' => true,
                'edit_cases' => true,
                'add_associate' => true,
                'record_lab_analysis' => true,
                'generate_reports' => false,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // 3. Sitrep Reports button is locked with tooltip on staff case page and sidebar
        $casePage = $this->actingAs($labStaff->fresh())->get('/Lab_Analysis/staff/case');
        $casePage->assertOk()
            ->assertSee('data-staff-sitrep-btn="header"', false)
            ->assertSee('data-staff-sitrep-btn="sidebar"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('data-locked-tooltip="Locked: Generate Reports &amp; PDFs permission is disabled by your Head of Unit"', false)
            ->assertSee('title="Locked: Generate Reports &amp; PDFs permission is disabled by your Head of Unit"', false);

        // 4. Live account-status watchdog reports can_generate_reports = false
        $this->actingAs($labStaff->fresh())
            ->getJson('/api/auth/account-status?path=/Lab_Analysis/staff/case')
            ->assertOk()
            ->assertJsonPath('can_generate_reports', false);

        // 5. Direct access to report and PDF routes is denied and redirected
        $this->actingAs($labStaff->fresh())
            ->get('/Lab_Analysis/staff/report')
            ->assertRedirect('/Lab_Analysis/staff/case');

        $this->actingAs($labStaff->fresh())
            ->get('/Lab_Analysis/lab_sitrep_pdf')
            ->assertRedirect('/Lab_Analysis/staff/case');
    }

    #[Test]
    public function evidence_management_head_permissions_page_excludes_digital_notifications_and_lab_toggles(): void
    {
        $emHead = User::factory()->head()->create([
            'fullname' => 'Vault Master EM Head',
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $emStaff = StaffAccount::create([
            'fullname' => 'Kwesi Vault Officer',
            'username' => 'kwesi_vault',
            'service_id' => 'EMU-301',
            'phone_number' => '0244111555',
            'unit' => 'Evidence Management',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // Verify definitions for unit
        $permDefs = User::permissionsForUnit('Evidence Management', '/Evidence_Management/permissions');
        $this->assertCount(5, $permDefs);
        $this->assertEquals([
            'create_cases',
            'edit_cases',
            'add_associate',
            'manage_exhibits',
            'generate_reports',
        ], array_keys($permDefs));

        // GET Evidence Management Head permissions console
        $resp = $this->actingAs($emHead)->get('/Evidence_Management/permissions');
        $resp->assertOk()
            ->assertSee('Kwesi Vault Officer')
            ->assertSee('Create New Cases')
            ->assertSee('Edit Case &amp; Suspects', false)
            ->assertSee('Add Associate')
            ->assertSee('Manage Exhibit Vault')
            ->assertSee('Generate Reports &amp; PDFs', false)
            // Explicitly verify removed toggles are not present
            ->assertDontSee('Digital Forensics Intake')
            ->assertDontSee('Notifications &amp; Handovers', false)
            ->assertDontSee('Lab &amp; Drug Screening', false)
            ->assertSee('5/5 active');

        // Verify AJAX poll table partial also respects exclusion
        $ajaxResp = $this->actingAs($emHead)->get('/Evidence_Management/permissions?poll=1');
        $ajaxResp->assertOk();
        $html = $ajaxResp->json('html');
        $this->assertStringContainsString('Manage Exhibit Vault', $html);
        $this->assertStringNotContainsString('Digital Forensics Intake', $html);
        $this->assertStringNotContainsString('Notifications &amp; Handovers', $html);
        $this->assertStringNotContainsString('Lab &amp; Drug Screening', $html);
    }
#[Test]
    public function evidence_management_head_toggling_off_manage_exhibits_locks_vault_button_and_blocks_routes(): void
    {
        $emHead = User::factory()->head()->create([
            'fullname' => 'Chief Vault Custodian Head',
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $emStaff = StaffAccount::create([
            'fullname' => 'Kojo Vault Officer',
            'username' => 'kojo_vault',
            'service_id' => 'EMU-888',
            'phone_number' => '0244111777',
            'unit' => 'Evidence Management',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // 1. Initially enabled staff can view unlocked Exhibit Vault button
        $initPage = $this->actingAs($emStaff)->get('/Evidence_Management/staff/case');
        $initPage->assertOk()
            ->assertSee('data-staff-vault-btn="header"', false)
            ->assertSee('data-staff-vault-btn="sidebar"', false)
            ->assertSee('href="' . url('/Evidence_Management/vault') . '"', false);

        // 2. Evidence Management Head toggles OFF manage_exhibits
        $this->actingAs($emHead)->postJson("/permissions/{$emStaff->id}/update", [
            'permissions' => [
                'create_cases' => true,
                'edit_cases' => true,
                'add_associate' => true,
                'manage_exhibits' => false,
                'generate_reports' => true,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // 3. Exhibit Vault button is locked with tooltip on staff case page and sidebar
        $casePage = $this->actingAs($emStaff->fresh())->get('/Evidence_Management/staff/case');
        $casePage->assertOk()
            ->assertSee('data-staff-vault-btn="header"', false)
            ->assertSee('data-staff-vault-btn="sidebar"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('data-locked-tooltip="Locked: Manage Exhibit Vault permission is disabled by your Head of Unit"', false)
            ->assertSee('title="Locked: Manage Exhibit Vault permission is disabled by your Head of Unit"', false);

        // 4. Live account-status watchdog reports can_manage_exhibits = false
        $this->actingAs($emStaff->fresh())
            ->getJson('/api/auth/account-status?path=/Evidence_Management/staff/case')
            ->assertOk()
            ->assertJsonPath('can_manage_exhibits', false);

        // 5. Direct access to vault routes is denied and redirected
        $this->actingAs($emStaff->fresh())
            ->get('/Evidence_Management/vault')
            ->assertRedirect('/Evidence_Management/staff/case');

        $this->actingAs($emStaff->fresh())
            ->get('/exhibit-vault')
            ->assertRedirect('/Evidence_Management/staff/case');
    }

    #[Test]
    public function evidence_management_head_toggling_off_generate_reports_locks_sitrep_reports_button_and_blocks_routes(): void
    {
        $emHead = User::factory()->head()->create([
            'fullname' => 'Chief Vault Custodian Head 2',
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $emStaff = StaffAccount::create([
            'fullname' => 'Esi Vault Officer',
            'username' => 'esi_vault',
            'service_id' => 'EMU-889',
            'phone_number' => '0244111778',
            'unit' => 'Evidence Management',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // 1. Initially enabled staff can view unlocked Sitrep Reports button
        $initPage = $this->actingAs($emStaff)->get('/Evidence_Management/staff/case');
        $initPage->assertOk()
            ->assertSee('data-staff-sitrep-btn="header"', false)
            ->assertSee('data-staff-sitrep-btn="sidebar"', false)
            ->assertSee('href="' . url('/Evidence_Management/staff/report') . '"', false);

        // 2. Evidence Management Head toggles OFF generate_reports
        $this->actingAs($emHead)->postJson("/permissions/{$emStaff->id}/update", [
            'permissions' => [
                'create_cases' => true,
                'edit_cases' => true,
                'add_associate' => true,
                'manage_exhibits' => true,
                'generate_reports' => false,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // 3. Sitrep Reports button is locked with tooltip on staff case page and sidebar
        $casePage = $this->actingAs($emStaff->fresh())->get('/Evidence_Management/staff/case');
        $casePage->assertOk()
            ->assertSee('data-staff-sitrep-btn="header"', false)
            ->assertSee('data-staff-sitrep-btn="sidebar"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('data-locked-tooltip="Locked: Generate Reports &amp; PDFs permission is disabled by your Head of Unit"', false)
            ->assertSee('title="Locked: Generate Reports &amp; PDFs permission is disabled by your Head of Unit"', false);

        // 4. Live account-status watchdog reports can_generate_reports = false
        $this->actingAs($emStaff->fresh())
            ->getJson('/api/auth/account-status?path=/Evidence_Management/staff/case')
            ->assertOk()
            ->assertJsonPath('can_generate_reports', false);

        // 5. Direct access to report and PDF routes is denied and redirected
        $this->actingAs($emStaff->fresh())
            ->get('/Evidence_Management/staff/report')
            ->assertRedirect('/Evidence_Management/staff/case');

        $this->actingAs($emStaff->fresh())
            ->get('/Evidence_Management/report_pdf')
            ->assertRedirect('/Evidence_Management/staff/case');
    }
#[Test]
    public function permissions_toggle_section_renders_all_toggles_in_one_line_grid(): void
    {
        $emHead = User::factory()->head()->create([
            'fullname' => 'Vault Master EM Head Single Line',
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $emStaff = StaffAccount::create([
            'fullname' => 'Kwesi Single Line Officer',
            'username' => 'kwesi_single_line',
            'service_id' => 'EMU-909',
            'phone_number' => '0244111888',
            'unit' => 'Evidence Management',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        $resp = $this->actingAs($emHead)->get('/Evidence_Management/permissions');
        $resp->assertOk()
            ->assertSee('grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3', false)
            ->assertDontSee('Intake new single/multiple suspect or exhibit-only cases')
            ->assertDontSee('Update case details, suspect biodata, and offence records')
            ->assertDontSee('Log exhibits, update seized items, and assign QR custody tags')
            ->assertDontSee('Export preliminary, analytical, SITREP, and unit audit reports');
    }

    #[Test]
    public function digital_forensics_head_toggling_off_generate_reports_locks_investigation_stat_and_report_and_denies_access(): void
    {
        $dpfHead = User::factory()->head()->create([
            'fullname' => 'Chief Cyber Head',
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $dpfStaff = StaffAccount::create([
            'fullname' => 'Kojo Cyber Officer',
            'username' => 'kojo_cyber',
            'service_id' => 'DPF-505',
            'phone_number' => '0244111777',
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
            'password' => bcrypt('password'),
        ]);

        // 1. Initially enabled staff can view unlocked Investigation Stat & Report button
        $initPage = $this->actingAs($dpfStaff)->get('/Digital_Forensics/staff/case');
        $initPage->assertOk()
            ->assertSee('data-staff-sitrep-btn="header"', false)
            ->assertSee('data-staff-sitrep-btn="sidebar"', false)
            ->assertSee('Investigation Stat &amp; Report', false)
            ->assertSee('href="' . url('/Digital_Forensics/staff/forensic-status') . '"', false);

        // 2. Digital Forensics Head toggles OFF generate_reports in /Digital_Forensics/permissions
        $this->actingAs($dpfHead)->postJson("/permissions/{$dpfStaff->id}/update", [
            'permissions' => [
                'create_cases' => true,
                'edit_cases' => true,
                'add_associate' => true,
                'generate_reports' => false,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // 3. Investigation Stat & Report is locked with tooltip and lock icon on staff case page and sidebar
        $casePage = $this->actingAs($dpfStaff->fresh())->get('/Digital_Forensics/staff/case');
        $casePage->assertOk()
            ->assertSee('data-staff-sitrep-btn="header"', false)
            ->assertSee('data-staff-sitrep-btn="sidebar"', false)
            ->assertSee('data-staff-investigation-stat-btn="sidebar"', false)
            ->assertSee('Investigation Stat &amp; Report', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('data-locked-tooltip="Locked: Generate Reports &amp; PDFs permission is disabled by your Head of Unit"', false)
            ->assertSee('title="Locked: Generate Reports &amp; PDFs permission is disabled by your Head of Unit"', false)
            ->assertDontSee('href="' . url('/Digital_Forensics/staff/forensic-status') . '"', false);

        // 4. Live account-status watchdog reports can_generate_reports = false
        $this->actingAs($dpfStaff->fresh())
            ->getJson('/api/auth/account-status?path=/Digital_Forensics/staff/case')
            ->assertOk()
            ->assertJsonPath('can_generate_reports', false);

        // 5. Direct access to forensic-status and PDF routes is denied and redirected
        $this->actingAs($dpfStaff->fresh())
            ->get('/Digital_Forensics/staff/forensic-status')
            ->assertRedirect('/Digital_Forensics/staff/case');

        $this->actingAs($dpfStaff->fresh())
            ->get('/Digital_Forensics/dpf-report-pdf')
            ->assertRedirect('/Digital_Forensics/staff/case');

        // 6. Digital Forensics Head toggles ON generate_reports again
        $this->actingAs($dpfHead)->postJson("/permissions/{$dpfStaff->id}/update", [
            'permissions' => [
                'create_cases' => true,
                'edit_cases' => true,
                'add_associate' => true,
                'generate_reports' => true,
            ],
        ])->assertOk()->assertJsonPath('success', true);

        // 7. Investigation Stat & Report is unlocked again
        $unlockedPage = $this->actingAs($dpfStaff->fresh())->get('/Digital_Forensics/staff/case');
        $unlockedPage->assertOk()
            ->assertSee('href="' . url('/Digital_Forensics/staff/forensic-status') . '"', false);
    }
}
