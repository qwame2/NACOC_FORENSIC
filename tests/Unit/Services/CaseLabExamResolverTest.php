<?php

namespace Tests\Unit\Services;

use App\Services\CaseLabExamResolver;
use App\Services\CaseReferenceService;
use App\Services\DrugExhibitClassifier;
use App\Services\ExhibitOwnershipResolver;
use PHPUnit\Framework\TestCase;

class CaseLabExamResolverTest extends TestCase
{
    private CaseLabExamResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new CaseLabExamResolver(
            new DrugExhibitClassifier(),
            new ExhibitOwnershipResolver(),
            new CaseReferenceService(),
        );
    }

    public function test_resolve_suspect_lab_exam_saved_from_docket_data_branches(): void
    {
        $case = (object) ['id' => null];

        // 1. Unsaved when no drug exhibit
        $resNoDrug = $this->resolver->resolveSuspectLabExamSaved(
            $case,
            ['lab_examination' => ['has_saved' => true]],
            false,
            'kwame asante',
            true,
            false,
            [],
        );
        $this->assertFalse($resNoDrug['suspectLabExamSaved']);
        $this->assertTrue($resNoDrug['caseHasAnySavedLab']);

        // 2. Saved via primary docketData['lab_examination']
        $resPrimarySaved = $this->resolver->resolveSuspectLabExamSaved(
            $case,
            ['lab_examination' => ['has_saved' => true, 'exhibit_owner_name' => 'Primary Suspect']],
            true,
            'kwame asante',
            true,
            false,
            [],
        );
        $this->assertTrue($resPrimarySaved['suspectLabExamSaved']);
        $this->assertTrue($resPrimarySaved['caseHasAnySavedLab']);

        // 3. Saved via associate_lab_examinations map for associate suspect
        $resAssocSaved = $this->resolver->resolveSuspectLabExamSaved(
            $case,
            [
                'associate_lab_examinations' => [
                    'Kofi Mensah' => ['has_saved' => true, 'analyst' => 'Analyst Mensah'],
                ],
            ],
            true,
            'kofi mensah',
            false,
            true,
            [],
        );
        $this->assertTrue($resAssocSaved['suspectLabExamSaved']);

        // 4. Unsaved for associate when only primary has saved lab
        $resAssocUnsaved = $this->resolver->resolveSuspectLabExamSaved(
            $case,
            ['lab_examination' => ['has_saved' => true, 'exhibit_owner_name' => 'Kwame Asante']],
            true,
            'kofi mensah',
            false,
            true,
            [],
        );
        $this->assertFalse($resAssocUnsaved['suspectLabExamSaved']);
        $this->assertTrue($resAssocUnsaved['caseHasAnySavedLab']);
    }

    public function test_resolve_suspect_dossier_lab_exam_handles_joint_and_associate_cards(): void
    {
        $case = new class {
            public function resolveLabExamForSuspect($sName, $id, $idx, $isAssoc, $isCoSuspect): array
            {
                return [
                    'has_saved' => true,
                    'is_joint' => !$isAssoc,
                    'owner_name' => $isAssoc ? $sName : 'All Suspects',
                    'substance_identification' => 'Cocaine',
                ];
            }
        };

        $savedJointLabExam = null;
        $savedJointLabHeadingName = null;

        // Primary suspect with joint lab exam populates $savedJointLabExam and does not render individual card
        $primaryRes = $this->resolver->resolveSuspectDossierLabExam(
            $case,
            'Kwame Asante',
            ['suspect_id' => 1],
            0,
            false,
            false,
            true,
            'Kwame Asante & Kofi Mensah',
            true,
            $savedJointLabExam,
            $savedJointLabHeadingName,
        );

        $this->assertFalse($primaryRes['shouldRenderSuspectLabCard']);
        $this->assertNotNull($savedJointLabExam);
        $this->assertSame('Kwame Asante & Kofi Mensah', $savedJointLabHeadingName);

        // Associate suspect with drug exhibit renders individual suspect lab card
        $assocRes = $this->resolver->resolveSuspectDossierLabExam(
            $case,
            'Ama Boateng',
            ['associate_id' => 2],
            1,
            true,
            false,
            true,
            'Kwame Asante & Kofi Mensah',
            true,
            $savedJointLabExam,
            $savedJointLabHeadingName,
        );

        $this->assertTrue($assocRes['shouldRenderSuspectLabCard']);
        $this->assertSame('Ama Boateng', $assocRes['labHeadingName']);
    }

    public function test_parse_presumptive_rows_parses_structured_and_fallback_strings(): void
    {
        $structured = 'YES ([1] Presence of: Cocaine Hydrochloride - Purity/Percentage: 84%; [2] Presence of: Levamisole - Purity/Percentage: 12%)';
        $rows = $this->resolver->parsePresumptiveRows($structured, false);

        $this->assertCount(2, $rows);
        $this->assertSame('YES', $rows[0]['result']);
        $this->assertSame('Cocaine Hydrochloride', $rows[0]['presence']);
        $this->assertSame('84%', $rows[0]['purity']);
        $this->assertSame('Levamisole', $rows[1]['presence']);
        $this->assertSame('12%', $rows[1]['purity']);

        $assocRows = $this->resolver->parsePresumptiveRows($structured, true);
        $this->assertCount(2, $assocRows);
        $this->assertSame('YES', $assocRows[0]['result']);
        $this->assertSame('Cocaine Hydrochloride', $assocRows[0]['presence']);

        $emptyRows = $this->resolver->parsePresumptiveRows('N/A', false);
        $this->assertSame([['result' => 'N/A', 'presence' => 'N/A', 'purity' => 'N/A']], $emptyRows);
    }
}

