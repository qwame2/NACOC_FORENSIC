<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewDocketNavigationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function crime_scene_support_view_docket_preserves_crime_scene_context_for_lab_created_case()
    {
        $officer = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
        ]);

        $labCase = ForensicCase::create([
            'case_number' => 'CAS-LAB-001',
            'title' => 'Exhibit Inspection from Lab Officer',
            'unit_name' => 'Lab Analysis',
            'commanding_officer' => 'Dr. Eric Mensah',
            'submitting_officer' => 'Insp. Lab Officer',
            'suspect_name' => 'Kofi Amoah',
            'status' => 'Pending Approval',
        ]);

        // Accessing review from Crime Scene Support context with from=Crime Scene Support
        $response = $this->actingAs($officer)->get("/cases/{$labCase->case_number}/review?from=Crime+Scene+Support");
        $response->assertStatus(200);

        // Sidebar should maintain CRIME SCENE SUPPORT UNIT header and return links
        $response->assertSee('CRIME SCENE SUPPORT UNIT');
        $response->assertSee('from=Crime+Scene+Support');
        $response->assertDontSee('Back to Case Directory?unit=Lab+Analysis');
    }

    #[Test]
    public function laboratory_analysis_view_docket_preserves_lab_context_for_crime_scene_created_case()
    {
        $labOfficer = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $fieldCase = ForensicCase::create([
            'case_number' => 'CAS-FIELD-002',
            'title' => 'Crime Scene Raid Seizure',
            'unit_name' => 'Crime Scene Support',
            'commanding_officer' => 'Capt. John Adu',
            'submitting_officer' => 'Insp. Field Officer',
            'suspect_name' => 'Kwame Boateng',
            'status' => 'Pending Approval',
        ]);

        // Accessing review from Lab Analysis context with from=Lab Analysis
        $response = $this->actingAs($labOfficer)->get("/cases/{$fieldCase->case_number}/review?from=Lab+Analysis");
        $response->assertStatus(200);

        // Sidebar should show LAB ANALYSIS UNIT header
        $response->assertSee('LAB ANALYSIS UNIT');
        $response->assertSee('from=Lab+Analysis');
    }

    #[Test]
    public function evidence_management_view_docket_preserves_evidence_management_context()
    {
        $officer = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-EVD-003',
            'title' => 'Evidence Vault Custody Log',
            'unit_name' => 'Lab Analysis',
            'commanding_officer' => 'Dr. Eric Mensah',
            'submitting_officer' => 'Insp. Vault Officer',
            'suspect_name' => 'Yaw Frimpong',
            'status' => 'Pending Approval',
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$case->case_number}/review?from=Evidence+Management");
        $response->assertStatus(200);

        $response->assertSee('EVIDENCE MANAGEMENT UNIT');
        $response->assertSee('from=Evidence+Management');
    }

    #[Test]
    public function digital_forensics_view_docket_preserves_digital_forensics_context()
    {
        $officer = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-DIG-004',
            'title' => 'Mobile Cyber Forensics Exhibit',
            'unit_name' => 'Lab Analysis',
            'commanding_officer' => 'Dr. Eric Mensah',
            'submitting_officer' => 'Insp. Cyber Analyst',
            'suspect_name' => 'Adwoa Mansa',
            'status' => 'Pending Approval',
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$case->case_number}/review?from=Digital+%26+Physical+Forensics");
        $response->assertStatus(200);

        $response->assertSee('DIGITAL &amp; PHYSICAL FORENSICS UNIT', false);
        $response->assertSee('from=Digital+%26+Physical+Forensics');
    }
}
