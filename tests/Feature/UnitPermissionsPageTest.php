<?php

namespace Tests\Feature;

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
            ->assertSee('Permissions');

        $this->actingAs($head)->get('/Evidence_Management/create')
            ->assertOk()
            ->assertSee('/Evidence_Management/permissions', false);

        $this->actingAs($head)->get('/Digital_Forensics/create')
            ->assertOk()
            ->assertSee('/Digital_Forensics/permissions', false);

        $this->actingAs($head)->get('/Crime_Scene_Support/create')
            ->assertOk()
            ->assertSee('/Crime_Scene_Support/permissions', false);

        $this->actingAs($head)->get('/Forensic_Head/cases')
            ->assertOk()
            ->assertSee('/Forensic_Head/permissions', false);
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

        $labStaff = User::factory()->create([
            'fullname' => 'Kofi Chemist',
            'service_id' => 'SD-901',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        $cssStaff = User::factory()->create([
            'fullname' => 'Ama Scene Officer',
            'service_id' => 'SD-902',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
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
            ->assertDontSee('<th class="py-3.5 px-4 whitespace-nowrap">Account</th>', false)
            ->assertSee('role="switch"', false)
            ->assertSee('View Case Directory')
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
            'permissions' => ['view_cases' => false],
        ])->assertStatus(403);

        // 4. Lab Head updates granular permissions for Lab staff
        $updateResp = $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'permissions' => [
                'view_cases' => true,
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
            ->assertJsonPath('officer.enabled', 5)
            ->assertJsonPath('officer.total', 8);

        $freshStaff = $labStaff->fresh();
        $this->assertTrue($freshStaff->hasPermission('view_cases'));
        $this->assertTrue($freshStaff->hasPermission('record_lab_analysis'));
        $this->assertFalse($freshStaff->hasPermission('create_cases'));
        $this->assertFalse($freshStaff->hasPermission('edit_cases'));

        // 5. Restrict All preset sets every capability to false cleanly
        $restrictResp = $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'preset' => 'restrict_all',
        ]);
        $restrictResp->assertOk()
            ->assertJsonPath('officer.enabled', 0);
        $this->assertFalse($labStaff->fresh()->hasPermission('view_cases'));

        // 6. Grant All preset enables all 8 capabilities
        $grantResp = $this->actingAs($labHead)->postJson("/permissions/{$labStaff->id}/update", [
            'preset' => 'grant_all',
        ]);
        $grantResp->assertOk()
            ->assertJsonPath('officer.enabled', 8);
        $this->assertTrue($labStaff->fresh()->hasPermission('create_cases'));

        // 7. CSS Head can load CSS permissions page ONLY for their own unit staff
        $this->actingAs($cssHead)->get('/Crime_Scene_Support/permissions')
            ->assertOk()
            ->assertSee('Ama Scene Officer')
            ->assertDontSee('Kofi Chemist');
    }
}

