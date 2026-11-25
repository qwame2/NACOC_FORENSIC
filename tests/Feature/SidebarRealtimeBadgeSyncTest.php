<?php

namespace Tests\Feature;

use App\Enums\ForensicUnit;
use App\Models\StaffAccount;
use App\Models\StaffCaseSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarRealtimeBadgeSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_to_sidebar_badge_counts_is_rejected(): void
    {
        $response = $this->getJson('/api/sidebar/badge-counts');
        $response->assertStatus(401);
    }

    public function test_sidebar_badge_counts_returns_correct_data_for_authenticated_head(): void
    {
        $head = User::factory()->create([
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'is_head' => true,
        ]);

        // Create a pending case approval
        StaffCaseSubmission::create([
            'case_number_preview' => 'CSS-SYNC-001',
            'unit_name' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'title' => 'Robbery Investigation',
            'status' => 'Pending Approval',
            'submitted_by' => 1,
            'submitted_by_name' => 'Officer Test',
            'payload' => ['sample' => true],
        ]);

        // Create a pending field officer approval
        StaffAccount::create([
            'service_id' => 'SYNC-STAFF-1',
            'username' => 'syncstaff1',
            'fullname' => 'Sync Staff One',
            'email' => 'syncstaff1@example.com',
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'password' => bcrypt('secret123'),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($head)->getJson('/api/sidebar/badge-counts?unit=' . urlencode(ForensicUnit::CRIME_SCENE_SUPPORT->value));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reviews_approvals' => 1,
                'field_officers' => 1,
            ]);

        $this->assertArrayHasKey('notifications', $response->json());
        $this->assertArrayHasKey('case_rollback', $response->json());
    }

    public function test_sidebar_badge_counts_returns_correct_rollback_count_for_staff(): void
    {
        $staff = StaffAccount::create([
            'service_id' => 'STAFF-RB-1',
            'username' => 'staffrb1',
            'fullname' => 'Staff Rollback Tester',
            'email' => 'staffrb1@example.com',
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'password' => bcrypt('secret123'),
            'status' => 'approved',
            'is_approved' => true,
        ]);

        StaffCaseSubmission::create([
            'case_number_preview' => 'CSS-RB-001',
            'unit_name' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'title' => 'Rolled Back Case 1',
            'status' => 'Rolled Back',
            'submitted_by' => $staff->id,
            'submitted_by_name' => $staff->fullname,
            'payload' => ['sample' => true],
        ]);

        $response = $this->actingAs($staff)->getJson('/api/sidebar/badge-counts?unit=' . urlencode(ForensicUnit::CRIME_SCENE_SUPPORT->value));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'case_rollback' => 1,
                'staff_rollback' => 1,
            ]);
    }

    public function test_staff_sidebar_renders_case_rollback_badge_and_poller_script(): void
    {
        $staff = StaffAccount::create([
            'service_id' => 'STAFF-RB-2',
            'username' => 'staffrb2',
            'fullname' => 'Staff View Tester',
            'email' => 'staffrb2@example.com',
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'password' => bcrypt('secret123'),
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($staff)->get('/Crime_Scene_Support/staff/case');
        $response->assertOk();
        $response->assertSee('sidebarStaffRollbackBadge', false);
        $response->assertSee('data-rollback-badge="sidebar"', false);
        $response->assertSee('window.__nacocSidebarBadgePollerInitialized', false);
        $response->assertSee('/api/sidebar/badge-counts', false);
    }

    public function test_unit_head_sidebar_renders_realtime_badge_elements_and_poller_script(): void
    {
        $head = User::factory()->create([
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'is_head' => true,
        ]);

        $response = $this->actingAs($head)->get('/Crime_Scene_Support/notifications');
        $response->assertOk();
        $response->assertSee('sidebarNotificationsBadge', false);
        $response->assertSee('sidebarReviewsApprovalsBadge', false);
        $response->assertSee('sidebarFieldOfficersBadge', false);
        $response->assertSee('window.__nacocSidebarBadgePollerInitialized', false);
        $response->assertSee('/api/sidebar/badge-counts', false);
    }

    public function test_forensic_head_sidebar_renders_notification_badge_and_poller_script(): void
    {
        $fh = User::factory()->create([
            'unit' => ForensicUnit::FORENSIC_DIRECTORATE->value,
            'is_head' => true,
        ]);

        $response = $this->actingAs($fh)->get('/Forensic_Head/cases');
        $response->assertOk();
        $response->assertSee('sidebarNotificationsBadge', false);
        $response->assertSee('window.__nacocSidebarBadgePollerInitialized', false);
        $response->assertSee('/api/sidebar/badge-counts', false);
    }
}
