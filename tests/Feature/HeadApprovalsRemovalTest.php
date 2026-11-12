<?php

namespace Tests\Feature;

use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeadApprovalsRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $headUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headUser = User::factory()->create([
            'fullname' => 'Director Kwame Mensah',
            'is_head' => true,
            'is_approved' => true,
            'unit' => 'Forensic Directorate',
        ]);
    }

    #[Test]
    public function forensic_head_sidebar_and_views_do_not_contain_head_approvals_and_sign_off(): void
    {
        // 1. Forensic Head dashboard (/Forensic_Head)
        $dashResp = $this->actingAs($this->headUser)->get('/Forensic_Head');
        $dashResp->assertStatus(200);
        $dashResp->assertDontSee('/Forensic_Head/approvals', false);
        $dashResp->assertDontSee('Head Approvals');

        // 2. Forensic Head cases register (/Forensic_Head/cases)
        $casesResp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases');
        $casesResp->assertStatus(200);
        $casesResp->assertDontSee('Head Approvals &amp; Sign-off', false);
        $casesResp->assertDontSee('/Forensic_Head/approvals', false);
        $casesResp->assertDontSee('sidebarPendingBadgeCount', false);

        // 3. Forensic Head Court Evidence & SITREP page (/court-evidence)
        $ceResp = $this->actingAs($this->headUser)->get('/court-evidence');
        $ceResp->assertStatus(200);
        $ceResp->assertDontSee('Head Approvals &amp; Sign-off', false);
        $ceResp->assertDontSee('/Forensic_Head/approvals', false);
        $ceResp->assertDontSee('sidebarPendingBadgeCount', false);

        // 4. Case show_review page
        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-8801',
            'title' => 'Sample Review Case',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Active',
        ]);
        $reviewResp = $this->actingAs($this->headUser)->get('/Forensic_Head/cases/' . $case->id . '/review');
        $reviewResp->assertStatus(200);
        $reviewResp->assertDontSee('Head Approvals &amp; Sign-off', false);
        $reviewResp->assertDontSee('/Forensic_Head/approvals', false);
        $reviewResp->assertDontSee('sidebarPendingBadgeCount', false);
    }

    #[Test]
    public function forensic_head_approvals_routes_and_endpoints_are_removed(): void
    {
        $this->actingAs($this->headUser)->get('/Forensic_Head/approvals')->assertStatus(404);
        $this->actingAs($this->headUser)->post('/Forensic_Head/users/' . $this->headUser->id . '/approve')->assertStatus(404);
        $this->actingAs($this->headUser)->post('/Forensic_Head/users/' . $this->headUser->id . '/deactivate')->assertStatus(404);
    }
}

