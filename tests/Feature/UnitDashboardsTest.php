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
        $respHead->assertSee('Evidence Management Unit Command Dashboard');
        $respHead->assertSee('Custody &amp; Vault Cases', false);

        $respStaff = $this->actingAs($officer)->get('/Evidence_Management/staff');
        $respStaff->assertStatus(200);
        $respStaff->assertSee('EVIDENCE MANAGEMENT');
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
        $respHead->assertSee('Digital Forensics Case Register');

        $respStaff = $this->actingAs($officer)->get('/Digital_Forensics/staff');
        $respStaff->assertStatus(200);
        $respStaff->assertSee('DIGITAL FORENSICS', false);
        $respStaff->assertSee('Digital &amp; Mobile Forensics Operations', false);

        $respStatus = $this->actingAs($officer)->get('/Digital_Forensics/forensic-status');
        $respStatus->assertStatus(200);
        $respStatus->assertSee('Forensic &amp; Investigation Status', false);

        $respPdf = $this->actingAs($officer)->get('/Digital_Forensics/dpf-report-pdf');
        $respPdf->assertStatus(200);
        $respPdf->assertSee('DIGITAL FORENSIC INVESTIGATION');
        $respPdf->assertSee('NARCOTICS CONTROL COMMISSION');
    }

    #[Test]
    public function review_extraction_file_back_button_redirects_to_digital_forensics_case_register()
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);
        $case = \App\Models\ForensicCase::create([
            'case_number' => 'DPF-2026-TEST1',
            'title' => 'Test Cyber Case',
            'unit_name' => 'Digital & Physical Forensics',
            'exhibit_category' => 'Electronics',
            'status' => 'Under Investigation',
        ]);

        $respReview = $this->actingAs($officer)->get('/cases/' . $case->case_number . '/review?from=Digital_Forensics_Register');
        $respReview->assertStatus(200);
        $respReview->assertSee('Back to Digital Forensics Case Register');
        $respReview->assertSee('/Digital_Forensics');
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
        $respHead->assertSee('Lab Analysis Unit Command Dashboard');
        $respHead->assertSee('Lab Analysis Cases');

        $respStaff = $this->actingAs($officer)->get('/Lab_Analysis/staff');
        $respStaff->assertStatus(200);
        $respStaff->assertSee('LAB ANALYSIS UNIT');
        $respStaff->assertSee('Lab Chemist Workstation');
    }
}
