<?php

namespace Tests\Unit\Services;

use App\Services\CaseUnitProgressService;
use PHPUnit\Framework\TestCase;

class CaseUnitProgressServiceTest extends TestCase
{
    private CaseUnitProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaseUnitProgressService();
    }

    public function test_unit_has_active_progress_and_recorded_officer(): void
    {
        $docketData = [
            'unit_progress' => [
                'Crime Scene Support' => ['status' => 'COMPLETED', 'officer' => 'Officer Mensah'],
                'Lab Analysis' => ['status' => 'PENDING', 'officer' => ''],
            ],
        ];

        $this->assertTrue($this->service->unitHasActiveProgress($docketData, 'Crime Scene Support'));
        $this->assertFalse($this->service->unitHasActiveProgress($docketData, 'Lab Analysis'));
        $this->assertFalse($this->service->unitHasActiveProgress($docketData, 'Digital & Physical Forensics'));

        $this->assertTrue($this->service->unitHasRecordedOfficer($docketData, 'Crime Scene Support'));
        $this->assertFalse($this->service->unitHasRecordedOfficer($docketData, 'Lab Analysis'));
    }

    public function test_resolve_unit_progress_includes_only_involved_units_in_canonical_order(): void
    {
        $case = (object) [
            'id' => 10,
            'case_number' => 'CSS-2026-0010',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Under Review',
            'submitting_officer' => 'CSI Mensah',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Case with CSS creator + drug exhibit (LAB involved), but NO digital exhibit or digital progress
        $res = $this->service->resolveUnitProgress(
            $case,
            [],
            'kwame asante',
            true,
            false,
            true,
            true,
            false,
            true,
            'Chief Analyst',
        );

        $this->assertTrue($res['cssInvolved']);
        $this->assertTrue($res['labInvolved']);
        $this->assertFalse($res['dpfInvolved']);
        $this->assertSame(
            ['Crime Scene Support', 'Lab Analysis', 'Evidence Management'],
            $res['unitsList'],
        );
        $this->assertNotContains('Head of Forensic Directorate', $res['unitsList']);
        $this->assertSame(3, $res['totalUnitsCount']);
        $this->assertSame('COMPLETED', $res['unitProgress']['Crime Scene Support']['status']);
        $this->assertSame('PENDING', $res['unitProgress']['Lab Analysis']['status']);
        $this->assertSame('PENDING', $res['unitProgress']['Evidence Management']['status']);
        $this->assertSame(1, $res['completedUnitsCount']);
        $this->assertFalse($res['hofDone']);
    }

    public function test_resolve_unit_progress_places_creator_unit_first_and_evidence_management_last(): void
    {
        $case = (object) [
            'id' => 25,
            'case_number' => 'DPF-2026-0025',
            'unit_name' => 'Digital & Physical Forensics',
            'status' => 'Under Review',
            'submitting_officer' => 'DPF Analyst',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $docketData = [
            'unit_progress' => [
                'Digital & Physical Forensics' => ['status' => 'COMPLETED', 'officer' => 'DPF Analyst'],
                'Evidence Management' => ['status' => 'COMPLETED', 'officer' => 'Vault Custodian'],
            ],
        ];

        $res = $this->service->resolveUnitProgress(
            $case,
            $docketData,
            'kwame asante',
            true,
            false,
            false,
            false,
            true,
            false,
        );

        // Creator is DPF -> DPF first, EM last, CSS and LAB not involved
        $this->assertSame(
            ['Digital & Physical Forensics', 'Evidence Management'],
            $res['unitsList'],
        );
        $this->assertSame(2, $res['completedUnitsCount']);
        $this->assertTrue($res['hofDone']);
    }

    public function test_lab_analysis_is_completed_when_gsa_reports_are_attached(): void
    {
        $case = (object) [
            'id' => 99,
            'case_number' => 'LAB-2026-0099',
            'unit_name' => 'Lab Analysis',
            'status' => 'Under Review',
            'submitting_officer' => 'Analyst Mensah',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // 1. Without GSA reports and without saved NLR exam -> PENDING
        $resPending = $this->service->resolveUnitProgress(
            $case,
            [],
            'john doe',
            true,
            false, // suspectLabExamSaved = false
            true,  // suspectHasDrug
            true,  // activeSuspectHasDrugExhibit
            false,
            false,
            'Lab Head',
            null,
            false, // hasGsaReports = false
        );

        $this->assertSame('PENDING', $resPending['unitProgress']['Lab Analysis']['status']);

        // 2. With GSA reports attached -> COMPLETED
        $resCompleted = $this->service->resolveUnitProgress(
            $case,
            [],
            'john doe',
            true,
            false, // suspectLabExamSaved = false
            true,  // suspectHasDrug
            true,  // activeSuspectHasDrugExhibit
            false,
            false,
            'Lab Head',
            null,
            true, // hasGsaReports = true
        );

        $this->assertSame('COMPLETED', $resCompleted['unitProgress']['Lab Analysis']['status']);
        $this->assertSame('GSA Report uploaded and attached', $resCompleted['unitProgress']['Lab Analysis']['notes']);

        // 3. Finalize unit progress preserves COMPLETED status when GSA reports exist
        $finalized = $this->service->finalizeUnitProgressAfterLabResolution(
            $resCompleted['unitProgress'],
            $resCompleted['unitsList'],
            $case,
            [],
            false, // suspectLabExamSaved = false
            true,  // suspectHasDrugExhibit
            'john doe',
            false,
            null,
            'Lab Head',
            true,  // hasGsaReports = true
        );

        $this->assertSame('COMPLETED', $finalized['unitProgress']['Lab Analysis']['status']);
        $this->assertSame('GSA Report uploaded and attached', $finalized['unitProgress']['Lab Analysis']['notes']);
    }
}

