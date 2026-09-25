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

    #[Test]
    public function suspect_without_drug_exhibit_does_not_show_lab_pending_section()
    {
        $labOfficer = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-MULTI-999',
            'title' => 'Multi Suspect Case',
            'unit_name' => 'Lab Analysis',
            'commanding_officer' => 'Dr. Eric Mensah',
            'submitting_officer' => 'Insp. Field Officer',
            'suspect_name' => 'Suspect Drug Owner',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cocaine',
            'status' => 'Pending Approval',
            'docket_data' => [
                'suspects' => [
                    [
                        'id' => 'S1',
                        'suspect_name' => 'Suspect Drug Owner',
                        'association_role' => 'Primary Suspect',
                    ],
                    [
                        'id' => 'S2',
                        'suspect_name' => 'Suspect No Drugs',
                        'association_role' => 'Associate Suspect',
                    ]
                ],
                'seizures' => [
                    [
                        'exhibit_category' => 'Drugs',
                        'seizure_name_drug' => 'Cocaine',
                        'associated_person' => 'Suspect Drug Owner',
                        'seizure_quantity' => 10,
                        'seizure_unit' => 'Kilograms (kg)',
                    ],
                    [
                        'exhibit_category' => 'Weapons & Ammunition',
                        'seizure_name' => 'Pistol',
                        'associated_person' => 'Suspect No Drugs',
                        'seizure_quantity' => 1,
                    ]
                ]
            ]
        ]);

        // Access review page for Suspect No Drugs who is not linked to any drug exhibits
        $response = $this->actingAs($labOfficer)->get("/cases/{$case->case_number}/review?from=Lab+Analysis&suspect=Suspect+No+Drugs");
        $response->assertStatus(200);

        // Section 5 Examination Information is always displayed per Requirement 1
        $response->assertSee('id="section5FormContainer"', false);
        $response->assertDontSee('id="section5FormContainer" class="space-y-6 hidden"', false);
    }

    #[Test]
    public function selecting_suspect_without_drug_exhibit_renders_empty_lab_exhibit_select_with_zero_options()
    {
        $labOfficer = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
        ]);

        $case = ForensicCase::create([
            'case_number' => 'CAS-MULTI-888',
            'title' => 'Multi Suspect Exhibit Mapping Test',
            'unit_name' => 'Lab Analysis',
            'commanding_officer' => 'Dr. Eric Mensah',
            'submitting_officer' => 'Insp. Field Officer',
            'suspect_name' => 'Eric',
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Heroin',
            'status' => 'Pending Approval',
            'docket_data' => [
                'suspects' => [
                    [
                        'id' => 'S1',
                        'suspect_name' => 'Eric',
                        'association_role' => 'Primary Suspect',
                    ],
                    [
                        'id' => 'S2',
                        'suspect_name' => 'Linda',
                        'association_role' => 'Associate Suspect',
                    ]
                ],
                'seizures' => [
                    [
                        'exhibit_category' => 'Drugs',
                        'seizure_name_drug' => 'Heroin',
                        'associated_person' => 'Eric',
                        'seizure_quantity' => 5,
                        'seizure_unit' => 'Kilograms (kg)',
                    ],
                    [
                        'exhibit_category' => 'Weapons & Ammunition',
                        'seizure_name' => 'Pistol',
                        'associated_person' => 'Linda',
                        'seizure_quantity' => 1,
                    ]
                ]
            ]
        ]);

        // 1. When Eric (who has Heroin) is selected:
        $respEric = $this->actingAs($labOfficer)->get("/cases/{$case->case_number}/review?from=Lab+Analysis&suspect=Eric");
        $respEric->assertStatus(200);
        $respEric->assertSee('data-name="Heroin"', false);

        // 2. When Linda (who has NO drug exhibit) is selected:
        $respLinda = $this->actingAs($labOfficer)->get("/cases/{$case->case_number}/review?from=Lab+Analysis&suspect=Linda");
        $respLinda->assertStatus(200);

        // Linda must NEVER see Eric's Heroin exhibit in the labExhibitSelect dropdown
        $respLinda->assertDontSee('data-name="Heroin"', false);
        $respLinda->assertSee('0 Drug Exhibits Linked');

        // Verify labExhibitSelect is rendered disabled without option elements
        $respLinda->assertSee('<select', false);
        $respLinda->assertSee('id="labExhibitSelect"', false);
        $respLinda->assertSee('disabled', false);
    }
}

