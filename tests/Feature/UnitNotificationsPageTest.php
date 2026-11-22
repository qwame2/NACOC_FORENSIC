<?php

namespace Tests\Feature;

use App\Models\CaseHandover;
use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitNotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_unit_head_portals_render_notifications_sidebar_button_with_unit_url(): void
    {
        $labHead = User::factory()->create(['unit' => 'Lab Analysis', 'is_head' => true, 'is_approved' => true]);
        $digitalHead = User::factory()->create(['unit' => 'Digital & Physical Forensics', 'is_head' => true, 'is_approved' => true]);
        $evidenceHead = User::factory()->create(['unit' => 'Evidence Management', 'is_head' => true, 'is_approved' => true]);
        $cssHead = User::factory()->create(['unit' => 'Crime Scene Support', 'is_head' => true, 'is_approved' => true]);
        $forensicHead = User::factory()->create(['unit' => 'Forensic Directorate', 'is_head' => true, 'is_approved' => true]);

        $this->actingAs($labHead)
            ->get('/Lab_Analysis')
            ->assertOk()
            ->assertSee('/Lab_Analysis/notifications', false)
            ->assertSee('Notifications');

        $this->actingAs($digitalHead)
            ->get('/Digital_Forensics')
            ->assertOk()
            ->assertSee('/Digital_Forensics/notifications', false)
            ->assertSee('Notifications');

        $this->actingAs($evidenceHead)
            ->get('/Evidence_Management')
            ->assertOk()
            ->assertSee('/Evidence_Management/notifications', false)
            ->assertSee('Notifications');

        $this->actingAs($cssHead)
            ->get('/Crime_Scene_Support/head')
            ->assertOk()
            ->assertSee('/Crime_Scene_Support/notifications', false)
            ->assertSee('Notifications');

        $this->actingAs($forensicHead)
            ->get('/Forensic_Head')
            ->assertOk()
            ->assertDontSee('/Forensic_Head/notifications', false);

        // Verify pages rendering the <aside> sidebar also include the Notifications button
        $this->actingAs($labHead)->get('/Lab_Analysis/create')->assertOk()->assertSee('/Lab_Analysis/notifications', false);
        $this->actingAs($digitalHead)->get('/Digital_Forensics/create')->assertOk()->assertSee('/Digital_Forensics/notifications', false);
        $this->actingAs($evidenceHead)->get('/Evidence_Management/vault')->assertOk()->assertSee('/Evidence_Management/notifications', false);
        $this->actingAs($cssHead)->get('/Crime_Scene_Support/create')->assertOk()->assertSee('/Crime_Scene_Support/notifications', false);
        $this->actingAs($forensicHead)->get('/Forensic_Head/cases')->assertOk()->assertSee('/Forensic_Head/notifications', false);
    }

    public function test_notifications_page_aggregates_real_db_events_filters_and_acknowledges_cleanly(): void
    {
        $labHead = User::factory()->create([
            'fullname' => 'Lab Head User',
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
        ]);
        $cssHead = User::factory()->create([
            'fullname' => 'CSS Head User',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);
        $pendingLabStaff = \App\Models\StaffAccount::create([
            'fullname' => 'Pending Lab Analyst',
            'username' => 'pending_lab_analyst_777',
            'unit' => 'Lab Analysis',
            'service_id' => 'NAC-LAU-777',
            'phone_number' => '0244000777',
            'is_approved' => false,
            'password' => bcrypt('password'),
        ]);

        // Cross-unit drug exhibit without suspect
        $drugCase = SingleSuspectCase::create([
            'case_number' => 'NCC-NOTIF-101',
            'title' => 'Border Narcotics Interception',
            'unit_name' => 'Crime Scene Support',
            'user_id' => $cssHead->id,
            'suspect_name' => 'Unknown',
            'status' => 'Awaiting Suspect Assignment',
        ]);
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $drugCase->id,
            'property_id' => 'NACOC/EMU/77101',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Methamphetamine Crystals',
            'drug_classification' => 'Methamphetamine',
            'associated_person' => 'Unassigned (No Suspect)',
            'source_requesting_unit' => 'Crime Scene Support',
        ]);

        // Pending Lab case sign-off
        $labPendingCase = SingleSuspectCase::create([
            'case_number' => 'NCC-NOTIF-102',
            'title' => 'Pending Lab Analysis Case',
            'unit_name' => 'Lab Analysis',
            'user_id' => $labHead->id,
            'suspect_name' => 'Yaw Kwarteng',
            'status' => 'Pending Approval',
        ]);

        // Pending CaseHandover to Lab Analysis
        $handover = CaseHandover::create([
            'case_number' => 'NCC-NOTIF-102',
            'single_suspect_case_id' => $labPendingCase->id,
            'from_user_id' => $cssHead->id,
            'from_unit' => 'Crime Scene Support',
            'to_user_id' => $labHead->id,
            'to_unit' => 'Lab Analysis',
            'status' => 'Pending Acknowledgement',
            'request_notes' => 'Urgent chemical potency test requested',
        ]);

        $pageHtml = $this->actingAs($labHead)
            ->get('/Lab_Analysis/notifications')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Methamphetamine Crystals', $pageHtml);
        $this->assertStringContainsString('Urgent chemical potency test requested', $pageHtml);
        $this->assertStringContainsString('Pending Lab Analysis Case', $pageHtml);
        $this->assertStringContainsString($pendingLabStaff->fullname, $pageHtml);

        // Polling endpoint + category filter
        $pollJson = $this->actingAs($labHead)
            ->getJson('/Lab_Analysis/notifications?poll=1&category=handover')
            ->assertOk()
            ->json();

        $this->assertTrue($pollJson['success']);
        $this->assertSame(1, $pollJson['stats']['handover']);
        $this->assertStringContainsString('Urgent chemical potency test requested', $pollJson['html']);
        $this->assertStringNotContainsString('Pending Lab Analyst', $pollJson['html']);

        // Acknowledge handover persists to DB
        $this->actingAs($labHead)
            ->postJson('/notifications/handovers/' . $handover->id . '/acknowledge')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($handover->fresh()->acknowledged_at);
        $this->assertSame('Acknowledged', $handover->fresh()->status);

        // Mark all read clears unread badge count in session
        $this->actingAs($labHead)
            ->postJson('/notifications/mark-read', ['all' => true, 'from' => 'Lab Analysis'])
            ->assertOk()
            ->assertJson(['success' => true, 'unreadCount' => 0]);

        // Audit zero console.log in notification views
        $indexSrc = file_get_contents(resource_path('views/notifications/index.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/console\.(log|warn|error|info|debug)/i', $indexSrc);
    }

    public function test_sidebar_notifications_badge_hidden_when_zero_and_bg_red_when_present(): void
    {
        $labHead = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
        ]);

        // When 0 notifications exist, badge is hidden (not visible)
        $respZero = $this->actingAs($labHead)->get('/Lab_Analysis/create');
        $respZero->assertOk();
        $this->assertMatchesRegularExpression('/id="sidebarNotificationsBadge"[^>]*class="[^"]*hidden[^"]*bg-red-600/', $respZero->getContent());

        // When an unread notification event exists, badge displays count and has bg-red-600
        $cssHead = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
        ]);
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-NOTIF-999',
            'title' => 'Test Notification Case',
            'unit_name' => 'Crime Scene Support',
            'user_id' => $cssHead->id,
            'suspect_name' => 'Test Suspect',
            'status' => 'Pending Approval',
        ]);
        CaseHandover::create([
            'case_number' => $case->case_number,
            'single_suspect_case_id' => $case->id,
            'from_user_id' => $cssHead->id,
            'from_unit' => 'Crime Scene Support',
            'to_user_id' => $labHead->id,
            'to_unit' => 'Lab Analysis',
            'status' => 'Pending Acknowledgement',
            'request_notes' => 'Red badge test handover',
        ]);

        $respActive = $this->actingAs($labHead)->get('/Lab_Analysis/create');
        $respActive->assertOk();
        $content = $respActive->getContent();
        $this->assertMatchesRegularExpression('/id="sidebarNotificationsBadge"[^>]*class="[^"]*bg-red-600/', $content);
        $this->assertDoesNotMatchRegularExpression('/id="sidebarNotificationsBadge"[^>]*class="[^"]*hidden/', $content);
    }
}
