<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SuspectNlrReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachedReportPlacementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function attached_report_files_container_appears_under_primary_and_co_suspect_seizures_container()
    {
        $officer = User::factory()->create([
            'unit' => 'Laboratory Analysis',
            'is_approved' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-TEST-4001',
            'title' => 'Republic vs John, Jane',
            'unit_name' => 'Laboratory Analysis',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'total_suspects_count' => 2,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'John Doe',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Associate Suspect',
            'suspect_name' => 'Jane Smith',
        ]);

        // Joint exhibit owned by all/joint suspects
        Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_suspect_id' => null,
            'associated_person' => 'All Suspects',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin (Brown Sugar)',
            'seizure_quantity' => 2,
            'seizure_unit' => 'Blocks',
        ]);

        // Attached NLR report file for the case
        SuspectNlrReport::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_number' => 'NCC-TEST-4001',
            'original_filename' => 'NLR_Report_Test.pdf',
            'stored_filename' => 'test.pdf',
            'file_path' => 'nlr_reports/test.pdf',
            'file_extension' => 'pdf',
            'file_size' => 1024,
            'user_id' => $officer->id,
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$multiCase->id}/review");

        $response->assertStatus(200);

        $content = $response->getContent();

        $dossierPos = strpos($content, 'Primary &amp; Co-Suspect Seizures &amp; Exhibits');
        if ($dossierPos === false) {
            $dossierPos = strpos($content, 'Primary & Co-Suspect Seizures & Exhibits');
        }

        $attachedPos = strpos($content, 'Attached Report Files (1)', $dossierPos);

        $this->assertNotFalse($dossierPos, 'Primary & Co-Suspect Seizures & Exhibits container must be present in show_review');
        $this->assertNotFalse($attachedPos, 'Attached Report Files (1) container must be present in show_review');
        $this->assertGreaterThan($dossierPos, $attachedPos, 'Attached Report Files (1) must be placed under Primary & Co-Suspect Seizures & Exhibits');
        $this->assertSame(1, substr_count($content, 'Attached Report Files (1)'), 'Joint Attached Report Files container must render strictly once on show_review');
    }

    #[Test]
    public function suspect_attached_report_files_render_once_for_single_suspect_and_multi_suspect_cases()
    {
        $officer = User::factory()->create([
            'unit' => 'Laboratory Analysis',
            'is_approved' => true,
        ]);

        // 1. Single-suspect case with an exhibit and preliminary report
        $singleCase = \App\Models\SingleSuspectCase::create([
            'case_number' => 'NCC-ONCE-SINGLE-01',
            'title' => 'Single Suspect Once Check',
            'unit_name' => 'Laboratory Analysis',
            'suspect_name' => 'Kofi Mensah',
            'exhibit_category' => 'Drugs',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'associated_person' => 'Kofi Mensah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Parcel',
        ]);

        \App\Models\SuspectPreliminaryReport::create([
            'single_suspect_case_id' => $singleCase->id,
            'case_number' => 'NCC-ONCE-SINGLE-01',
            'original_filename' => 'Preliminary_Report_Single.pdf',
            'stored_filename' => 'prelim_single.pdf',
            'file_path' => '/Lab_Analysis/preliminary-report-pdf/NCC-ONCE-SINGLE-01',
            'file_extension' => 'pdf',
            'file_size' => 1024,
            'status' => 'active',
        ]);

        foreach ([
            "/Lab_Analysis/cases/{$singleCase->case_number}/review",
            "/cases/{$singleCase->case_number}/review",
        ] as $url) {
            $resp = $this->actingAs($officer)->get($url);
            if ($resp->isRedirection()) {
                $resp = $this->actingAs($officer)->get($resp->headers->get('Location'));
            }
            $resp->assertOk();
            $html = $resp->getContent();
            $this->assertSame(1, substr_count($html, 'gsa-attached-files'), "Expected strictly 1 Attached Report Files container on {$url}");
            $this->assertSame(1, substr_count($html, 'Attached Report Files (1)'), "Expected Attached Report Files (1) heading once on {$url}");
            $this->assertSame(1, substr_count($html, 'title="Preliminary_Report_Single.pdf"'), "Expected strictly 1 report item on {$url}");
        }

        // 2. Multi-suspect case where suspect 1 owns a suspect-specific report and joint exhibits exist
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-ONCE-MULTI-02',
            'title' => 'Republic vs Kwaku, Ama',
            'unit_name' => 'Laboratory Analysis',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'total_suspects_count' => 2,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kwaku Osei',
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Co-Suspect',
            'suspect_name' => 'Ama Serwaa',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'associated_person' => 'All Suspects',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Shared Encrypted Drive',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Unit',
        ]);

        \App\Models\SuspectGsaReport::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_suspect_id' => $suspect1->id,
            'case_number' => 'NCC-ONCE-MULTI-02',
            'original_filename' => 'kwaku_only_gsa.pdf',
            'stored_filename' => 'kwaku_only_gsa.pdf',
            'file_path' => 'gsa_reports/kwaku_only_gsa.pdf',
            'file_extension' => 'pdf',
            'file_size' => 2048,
            'status' => 'active',
        ]);

        $multiHtml = $this->actingAs($officer)->get("/Lab_Analysis/cases/{$multiCase->case_number}/review")->assertOk()->getContent();
        $this->assertSame(1, substr_count($multiHtml, 'gsa-attached-files'), 'Suspect-specific report must appear strictly once (under its suspect dossier, not duplicated in joint section)');
        $this->assertSame(1, substr_count($multiHtml, 'Attached Report Files (1)'));
        $this->assertSame(1, substr_count($multiHtml, 'title="kwaku_only_gsa.pdf"'));
    }

    #[Test]
    public function attached_report_files_always_render_directly_on_top_of_seizures_commodities_inventory_across_review_views(): void
    {
        $head = User::factory()->create([
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staff = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-ORDER-4002',
            'laboratory_number' => 'Lab No. 099/2026',
            'title' => 'Republic vs John Doe, Jane Smith',
            'unit_name' => 'Lab Analysis',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $head->id,
            'total_suspects_count' => 2,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'John Doe',
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Co-Suspect',
            'suspect_name' => 'Jane Smith',
        ]);

        $primaryExhibit = Exhibit::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect1->id,
            'associated_person' => 'John Doe',
            'exhibit_number' => 'EXH/2026/PRIM-98',
            'exhibit_category' => 'Drugs',
            'exhibit_type' => 'Drugs',
            'commodity_name' => 'Cocaine',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Parcel',
        ]);

        Exhibit::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $multiCase->id,
            'case_suspect_id' => null,
            'associated_person' => 'All Suspects',
            'exhibit_number' => 'EXH/2026/JOINT-99',
            'exhibit_category' => 'Drugs',
            'exhibit_type' => 'Drugs',
            'commodity_name' => 'Heroin (Brown Sugar)',
            'seizure_quantity' => 2,
            'seizure_unit' => 'Blocks',
        ]);

        \App\Models\LaboratoryExaminationResult::create([
            'case_type' => 'multiple',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect1->id,
            'exhibit_id' => $primaryExhibit->id,
            'laboratory_number' => 'Lab No. 099/2026',
            'exam_reference' => 'NACOC/FSD/LAB/2026/0099',
            'analyst' => 'Lab Analyst',
            'date_completed' => '2026-03-14',
            'substance_identification' => 'Positive',
            'detected_substances' => ['Heroin'],
            'findings' => 'Confirmed Heroin.',
            'has_saved' => true,
        ]);

        SuspectNlrReport::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_number' => 'NCC-ORDER-4002',
            'original_filename' => 'NLR_Joint_Order_Check.pdf',
            'stored_filename' => 'nlr_joint_order.pdf',
            'file_path' => 'nlr_reports/nlr_joint_order.pdf',
            'file_extension' => 'pdf',
            'file_size' => 1024,
            'user_id' => $head->id,
        ]);

        $headRoutes = [
            "/cases/{$multiCase->id}/review?from=Crime_Scene_Support",
            "/cases/{$multiCase->id}/review?from=Lab_Analysis",
            "/cases/{$multiCase->id}/review?from=Digital_Forensics",
            "/cases/{$multiCase->id}/review?from=Evidence_Management",
            "/Forensic_Head/cases/{$multiCase->id}/review",
        ];

        $staffRoutes = [
            "/Crime_Scene_Support/staff/cases/{$multiCase->case_number}/review",
            "/Lab_Analysis/staff/cases/{$multiCase->case_number}/review",
            "/Evidence_Management/staff/cases/{$multiCase->case_number}/review",
            "/Digital_Forensics/staff/cases/{$multiCase->case_number}/review",
            "/Forensic_Head/staff/cases/{$multiCase->case_number}/review",
        ];

        $assertOrder = function (string $html, string $route): void {
            $dossierPos = strpos($html, 'Primary &amp; Co-Suspect Seizures &amp; Exhibits');
            if ($dossierPos === false) {
                $dossierPos = strpos($html, 'Primary & Co-Suspect Seizures & Exhibits');
            }
            $this->assertNotFalse($dossierPos, "Primary & Co-Suspect Seizures & Exhibits must render on [{$route}]");

            $seizuresPos = strpos($html, 'Seizures (Commodities Inventory)');
            $this->assertNotFalse($seizuresPos, "Seizures (Commodities Inventory) must render on [{$route}]");

            $jointLabCardPos = strpos($html, 'data-lab-card="true"', $dossierPos);
            $this->assertNotFalse($jointLabCardPos, "Joint LABORATORY EXAMINATION RESULT card must render under Primary & Co-Suspect Seizures & Exhibits on [{$route}]");

            $jointAttachedPos = strpos($html, 'Attached Report Files', $dossierPos);
            $this->assertNotFalse($jointAttachedPos, "Joint Attached Report Files must render under Primary & Co-Suspect Seizures & Exhibits on [{$route}]");

            $jointReportFilePos = strpos($html, 'NLR_Joint_Order_Check.pdf');
            $this->assertNotFalse($jointReportFilePos, "NLR_Joint_Order_Check.pdf must render on [{$route}]");

            $this->assertLessThan($jointAttachedPos, $jointLabCardPos, "LABORATORY EXAMINATION RESULT must NOT sit between Attached Report Files and Seizures (Commodities Inventory) on [{$route}]");
            $this->assertLessThan($seizuresPos, $jointAttachedPos, "Attached Report Files must appear directly on top of Seizures (Commodities Inventory) on [{$route}]");
            $this->assertGreaterThan($jointAttachedPos, $jointReportFilePos, "NLR_Joint_Order_Check.pdf must be inside joint Attached Report Files on [{$route}]");
            $this->assertLessThan($seizuresPos, $jointReportFilePos, "Joint Attached Report Files must precede Seizures (Commodities Inventory) on [{$route}]");
        };

        foreach ($headRoutes as $route) {
            $resp = $this->followingRedirects()->actingAs($head)->get($route)->assertOk();
            $assertOrder($resp->getContent(), $route);
        }

        foreach ($staffRoutes as $route) {
            $resp = $this->followingRedirects()->actingAs($staff)->get($route)->assertOk();
            $assertOrder($resp->getContent(), $route);
        }
    }

    #[Test]
    public function attached_report_files_render_on_top_of_seizures_inventory_without_joint_exhibits_and_on_submission_review(): void
    {
        $labHead = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $staff = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_staff' => true,
            'is_approved' => true,
        ]);

        // Case WITHOUT 'All Suspects' joint exhibit so $isJointExhibitCase is false,
        // yet a case-level NLR report still exists.
        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-NOJOINT-4003',
            'title' => 'Republic vs Kojo, Esi',
            'unit_name' => 'Lab Analysis',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $labHead->id,
            'total_suspects_count' => 2,
        ]);

        $s1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kojo Mensah',
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Associate Suspect',
            'suspect_name' => 'Esi Boateng',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'case_suspect_id' => $s1->id,
            'associated_person' => 'Kojo Mensah',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cannabis',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Slab',
        ]);

        SuspectNlrReport::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'case_number' => 'NCC-NOJOINT-4003',
            'original_filename' => 'NLR_Case_Level_Report.pdf',
            'stored_filename' => 'nlr_case_level.pdf',
            'file_path' => 'nlr_reports/nlr_case_level.pdf',
            'file_extension' => 'pdf',
            'file_size' => 1024,
            'user_id' => $labHead->id,
        ]);

        $html = $this->followingRedirects()
            ->actingAs($labHead)
            ->get("/cases/{$multiCase->id}/review?from=Lab_Analysis")
            ->assertOk()
            ->getContent();

        $attachedPos = strpos($html, 'Attached Report Files');
        $seizuresPos = strpos($html, 'Seizures (Commodities Inventory)');
        $this->assertNotFalse($attachedPos);
        $this->assertNotFalse($seizuresPos);
        $this->assertLessThan($seizuresPos, $attachedPos);

        // Staff submission review page (head/submission_review.blade.php)
        $submission = \App\Models\StaffCaseSubmission::create([
            'case_number' => 'NCC-SUBREV-4004',
            'title' => 'Submission Review Report Order',
            'unit_name' => 'Lab Analysis',
            'action_type' => 'create',
            'status' => 'pending_head_approval',
            'submitted_by_user_id' => $staff->id,
            'payload' => [
                'case_number' => 'NCC-SUBREV-4004',
                'title' => 'Submission Review Report Order',
                'unit_name' => 'Lab Analysis',
                'suspect_name' => 'Kojo Mensah',
                'total_suspects_count' => 1,
                'seizures' => [
                    [
                        'exhibit_category' => 'Drugs',
                        'commodity_name' => 'Cocaine',
                        'seizure_quantity' => '1',
                        'seizure_unit' => 'Parcel',
                        'associated_person' => 'Kojo Mensah',
                    ],
                ],
            ],
        ]);

        SuspectNlrReport::create([
            'case_number' => 'NCC-SUBREV-4004',
            'original_filename' => 'NLR_Submission_Report.pdf',
            'stored_filename' => 'nlr_submission_report.pdf',
            'file_path' => 'nlr_reports/nlr_submission_report.pdf',
            'file_extension' => 'pdf',
            'file_size' => 1024,
            'user_id' => $staff->id,
        ]);

        $subHtml = $this->followingRedirects()
            ->actingAs($labHead)
            ->get("/Lab_Analysis/reviews-approvals/{$submission->id}")
            ->assertOk()
            ->getContent();

        $subAttachedPos = strpos($subHtml, 'Attached Report Files');
        $subSeizuresPos = strpos($subHtml, 'Seizures (Commodities Inventory)');
        $this->assertNotFalse($subAttachedPos, 'Attached Report Files must render on head/submission_review.blade.php');
        $this->assertNotFalse($subSeizuresPos, 'Seizures (Commodities Inventory) must render on head/submission_review.blade.php');
        $this->assertLessThan($subSeizuresPos, $subAttachedPos, 'Attached Report Files must appear BEFORE Seizures (Commodities Inventory) on head/submission_review.blade.php');
    }
}
