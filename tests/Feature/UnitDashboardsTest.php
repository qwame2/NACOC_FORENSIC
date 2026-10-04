<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnitDashboardsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function evidence_management_head_and_staff_dashboards_render_successfully()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $respHead = $this->actingAs($officer)->get('/Evidence_Management/head');
        $respHead->assertStatus(200);
        $respHead->assertSee('EVIDENCE MANAGEMENT UNIT');
        $respHead->assertSee('Evidence Vault &amp; Custody Headquarters', false);

        $respStaff = $this->actingAs($officer)->get('/Evidence_Management/staff');
        $respStaff->assertStatus(200);
        $respStaff->assertSee('EVIDENCE MANAGEMENT UNIT');
        $respStaff->assertSee('Evidence Vault Staff Operations');
    }

    #[Test]
    public function digital_forensics_head_and_staff_dashboards_render_successfully()
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $respHead = $this->actingAs($officer)->get('/Digital_Forensics/head');
        $respHead->assertStatus(200);
        $respHead->assertSee('DIGITAL &amp; PHYSICAL FORENSICS UNIT', false);
        $respHead->assertSee('Digital &amp; Mobile Cyber Forensics HQ', false);

        $respStaff = $this->actingAs($officer)->get('/Digital_Forensics/staff');
        $respStaff->assertStatus(200);
        $respStaff->assertSee('DIGITAL &amp; PHYSICAL FORENSICS UNIT', false);
        $respStaff->assertSee('Digital &amp; Mobile Forensics Operations', false);

        $respStatus = $this->actingAs($officer)->get('/Digital_Forensics/forensic-status');
        $respStatus->assertStatus(200);
        $respStatus->assertSee('Forensic &amp; Investigation Status', false);
    }

    #[Test]
    public function lab_analysis_head_and_staff_dashboards_render_successfully()
    {
        $officer = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $respHead = $this->actingAs($officer)->get('/Lab_Analysis/head');
        $respHead->assertStatus(200);
        $respHead->assertSee('LAB ANALYSIS UNIT');
        $respHead->assertSee('Laboratory Analysis Headquarters');

        $respStaff = $this->actingAs($officer)->get('/Lab_Analysis/staff');
        $respStaff->assertStatus(200);
        $respStaff->assertSee('LAB ANALYSIS UNIT');
        $respStaff->assertSee('Lab Chemist Workstation');
    }
}
