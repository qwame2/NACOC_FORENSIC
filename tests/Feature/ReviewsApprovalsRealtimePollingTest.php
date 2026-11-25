<?php

namespace Tests\Feature;

use App\Enums\ForensicUnit;
use App\Models\StaffCaseSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewsApprovalsRealtimePollingTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_to_reviews_approvals_polling_is_rejected(): void
    {
        $response = $this->getJson('/Crime_Scene_Support/reviews-approvals?poll=1');
        $this->assertTrue(in_array($response->status(), [401, 403, 302], true));
    }

    public function test_non_head_staff_user_is_rejected_from_polling(): void
    {
        $staff = User::factory()->create([
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'is_head' => false,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($staff)->getJson('/Crime_Scene_Support/reviews-approvals?poll=1');
        $response->assertStatus(403);
    }

    public function test_unit_head_can_silently_poll_pending_staff_submissions(): void
    {
        $head = User::factory()->create([
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'is_head' => true,
            'is_approved' => true,
        ]);

        $submission = StaffCaseSubmission::create([
            'case_number_preview' => 'CSS-POLL-001',
            'unit_name' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'title' => 'Armed Robbery Docket',
            'suspect_name' => 'Kojo Silent Poller',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'submitted_by' => 99,
            'submitted_by_name' => 'Officer Poller',
            'payload' => [
                'title' => 'Armed Robbery Docket',
                'suspect_name' => 'Kojo Silent Poller',
                'priority' => 'HIGH',
            ],
        ]);

        $response = $this->actingAs($head)->getJson('/Crime_Scene_Support/reviews-approvals?poll=1');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'counts' => [
                    'pending_total'   => 1,
                    'awaiting_review' => 1,
                    'rolled_back'     => 0,
                    'urgent'          => 1,
                ],
            ]);

        $this->assertArrayHasKey('html', $response->json());
        $this->assertArrayHasKey('signature', $response->json());
        $this->assertStringContainsString('Kojo Silent Poller', $response->json('html'));
        $this->assertStringContainsString('SUB-' . str_pad((string) $submission->id, 4, '0', STR_PAD_LEFT), $response->json('html'));
    }

    public function test_polling_returns_updated_data_when_submission_added_or_status_changed(): void
    {
        $head = User::factory()->create([
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'is_head' => true,
            'is_approved' => true,
        ]);

        // Empty state initially
        $initialRes = $this->actingAs($head)->getJson('/Crime_Scene_Support/reviews-approvals?poll=1');
        $initialRes->assertOk();
        $this->assertEquals(0, $initialRes->json('counts.pending_total'));
        $initialSig = $initialRes->json('signature');

        // New submission created
        $sub = StaffCaseSubmission::create([
            'case_number_preview' => 'CSS-POLL-002',
            'unit_name' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'title' => 'Burglary Case',
            'suspect_name' => 'Ama Live Polling',
            'priority' => 'CRITICAL',
            'status' => 'Pending Approval',
            'submitted_by' => 101,
            'submitted_by_name' => 'Officer Live',
            'payload' => [
                'title' => 'Burglary Case',
                'suspect_name' => 'Ama Live Polling',
                'priority' => 'CRITICAL',
            ],
        ]);

        $afterRes = $this->actingAs($head)->getJson('/Crime_Scene_Support/reviews-approvals?poll=1');
        $afterRes->assertOk();
        $this->assertEquals(1, $afterRes->json('counts.pending_total'));
        $this->assertEquals(1, $afterRes->json('counts.urgent'));
        $this->assertNotEquals($initialSig, $afterRes->json('signature'));
        $this->assertStringContainsString('Ama Live Polling', $afterRes->json('html'));
    }

    public function test_reviews_approvals_view_renders_polling_script_and_queue_container(): void
    {
        $head = User::factory()->create([
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($head)->get('/Crime_Scene_Support/reviews-approvals');
        $response->assertOk();
        $response->assertSee('pendingQueueContainer', false);
        $response->assertSee('kpiPendingReviewCount', false);
        $response->assertSee('kpiRolledBackCount', false);
        $response->assertSee('kpiUrgentPendingCount', false);
        $response->assertSee('pollPendingQueue', false);
        $response->assertSee('setInterval(() => pollPendingQueue(), 3000)', false);
    }

    public function test_pending_queue_displays_both_suspect_front_images_for_multiple_suspect_case(): void
    {
        $head = User::factory()->create([
            'unit' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'is_head' => true,
            'is_approved' => true,
        ]);

        $sub = StaffCaseSubmission::create([
            'case_number_preview' => 'CSS-MULTI-001',
            'unit_name' => ForensicUnit::CRIME_SCENE_SUPPORT->value,
            'title' => 'Armed Robbery Syndicate Case',
            'suspect_name' => 'Kofi Primary',
            'priority' => 'HIGH',
            'status' => 'Pending Approval',
            'submitted_by' => 99,
            'submitted_by_name' => 'Officer Investigating',
            'payload' => [
                'title' => 'Armed Robbery Syndicate Case',
                'suspect_name' => 'Kofi Primary',
                'suspect_docket_mode' => 'MULTIPLE',
                'suspects' => [
                    [
                        'suspect_number' => 1,
                        'suspect_name' => 'Kofi Primary',
                        'role' => 'Primary Suspect',
                        'mugshot_front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                    ],
                    [
                        'suspect_number' => 2,
                        'suspect_name' => 'Kwaku Co',
                        'role' => 'Co-Suspect',
                        'mugshot_front' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                    ],
                ],
            ],
        ]);

        // Direct GET view render
        $response = $this->actingAs($head)->get('/Crime_Scene_Support/reviews-approvals');
        $response->assertOk();
        $response->assertSee('Kofi Primary');
        $response->assertSee('Kwaku Co');
        $response->assertSee('Primary Suspect: Kofi Primary');
        $response->assertSee('Co-Suspect: Kwaku Co');
        $response->assertSee('S1');
        $response->assertSee('S2');
        $response->assertSee('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        // Silent Polling JSON response
        $pollRes = $this->actingAs($head)->getJson('/Crime_Scene_Support/reviews-approvals?poll=1');
        $pollRes->assertOk();
        $pollHtml = $pollRes->json('html');
        $this->assertStringContainsString('Kofi Primary', $pollHtml);
        $this->assertStringContainsString('Kwaku Co', $pollHtml);
        $this->assertStringContainsString('S1', $pollHtml);
        $this->assertStringContainsString('S2', $pollHtml);
        $this->assertStringContainsString('Primary Suspect: Kofi Primary', $pollHtml);
        $this->assertStringContainsString('Co-Suspect: Kwaku Co', $pollHtml);
    }
}

