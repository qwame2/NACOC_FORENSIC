<?php

namespace Tests\Unit\Services;

use App\Services\CaseExhibitResolver;
use App\Services\CasePhotoResolver;
use App\Services\DrugExhibitClassifier;
use App\Services\ExhibitOwnershipResolver;
use PHPUnit\Framework\TestCase;

class CaseExhibitResolverTest extends TestCase
{
    private CaseExhibitResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new CaseExhibitResolver(
            new DrugExhibitClassifier(),
            new ExhibitOwnershipResolver(),
            new CasePhotoResolver(),
        );
    }

    public function test_resolve_suspect_linked_exhibits_filters_by_primary_associate_and_joint(): void
    {
        $case = (object) [
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'Cocaine',
            'seizure_name' => 'Cocaine Hydrochloride',
        ];

        $docketData = [
            'seizures' => [
                ['seizure_name' => 'Primary Brick', 'associated_person' => 'Primary Suspect'],
                ['seizure_name' => 'Joint Parcel', 'associated_person' => 'All Suspects (Joint Ownership)'],
                ['seizure_name' => 'Unowned Item', 'associated_person' => ''],
            ],
            'associated_seizures' => [
                ['seizure_name' => 'Associate Stash', 'associated_person' => 'Kofi Mensah'],
            ],
        ];

        $primaryResult = $this->resolver->resolveSuspectLinkedExhibits($case, $docketData, 'kwame asante', true);
        $this->assertCount(4, $primaryResult['suspectLinkedExhibits']);

        $assocResult = $this->resolver->resolveSuspectLinkedExhibits($case, $docketData, 'kofi mensah', false);
        // Should include Joint Parcel + Associate Stash, but not Primary Brick, Unowned Item, or case root drug
        $this->assertCount(2, $assocResult['suspectLinkedExhibits']);
        $names = array_column($assocResult['suspectLinkedExhibits'], 'seizure_name');
        $this->assertContains('Joint Parcel', $names);
        $this->assertContains('Associate Stash', $names);
    }

    public function test_resolve_all_case_exhibits_deduplicates_and_extracts_joint_exhibits(): void
    {
        $case = (object) [
            'suspect_name' => 'Kwame Asante',
            'evidence_photos' => ['exhibits/case-level.jpg'],
        ];

        $docketData = [
            'seizures' => [
                [
                    'exhibit_number' => 'EXH-2026-01',
                    'category' => 'Drugs',
                    'seizure_name' => 'Cocaine Brick',
                    'associated_person' => 'All Suspects',
                    'photos' => ['exhibits/brick1.jpg'],
                ],
                // Duplicate by exhibit_number
                [
                    'exhibit_number' => 'EXH-2026-01',
                    'category' => 'Drugs',
                    'seizure_name' => 'Cocaine Brick Duplicate',
                ],
                // Distinct exhibit owned by single associate
                [
                    'exhibit_number' => 'EXH-2026-02',
                    'category' => 'Electronics',
                    'seizure_name' => 'iPhone 15 Pro',
                    'associated_person' => 'Ama Boateng',
                ],
            ],
        ];

        $res = $this->resolver->resolveAllCaseExhibits(
            $case,
            $docketData,
            true,
            ['kwame asante', 'kofi mensah'],
            'Kwame Asante & Kofi Mensah',
        );

        $this->assertCount(2, $res['allCaseExhibits']);
        $this->assertCount(1, $res['jointCaseExhibits']);
        $this->assertTrue($res['isJointExhibitCase']);
        $this->assertSame('Kwame Asante & Kofi Mensah', $res['jointDossierHeadingName']);
        $this->assertContains('exhibits/brick1.jpg', $res['allCaseExhibitPhotos']);
        $this->assertContains('exhibits/case-level.jpg', $res['allCaseExhibitPhotos']);
    }

    public function test_get_exhibits_for_suspect_separates_joint_from_exclusive_exhibits(): void
    {
        $case = new class {
            public function getExhibitsForSuspect($sName, $id, $idx, $isAssoc): array
            {
                return [
                    ['id' => 1, 'seizure_name' => 'Joint Cocaine', 'owner_name' => 'Joint Ownership'],
                    ['id' => 2, 'seizure_name' => 'Personal Phone', 'owner_name' => 'Kofi Mensah'],
                ];
            }
        };

        $capturedJoint = [];
        $res = $this->resolver->getExhibitsForSuspect(
            $case,
            'Kofi Mensah',
            ['suspect_id' => 10],
            1,
            true,
            ['kwame asante'],
            $capturedJoint,
        );

        $this->assertCount(2, $res['rawSuspectExhibits']);
        $this->assertCount(1, $res['suspectExhibits']);
        $this->assertSame('Personal Phone', $res['suspectExhibits'][0]['seizure_name']);
        $this->assertCount(1, $capturedJoint);
        $this->assertSame('Joint Cocaine', $capturedJoint[0]['seizure_name']);
    }

    public function test_resolve_final_joint_exhibits_summary_for_single_and_multiple_suspects(): void
    {
        $singleCase = (object) ['suspect_name' => 'Solo Suspect', 'is_multiple' => false];
        $allExhibits = [
            ['id' => 1, 'seizure_name' => 'Cannabis Slab', 'owner_name' => 'Solo Suspect'],
        ];

        $singleSummary = $this->resolver->resolveFinalJointExhibitsSummary(
            $singleCase,
            $allExhibits,
            [],
            ['solo suspect'],
            'Solo Suspect',
            false,
            false,
            [['suspect_name' => 'Solo Suspect']],
            [],
        );

        $this->assertFalse($singleSummary['isMultiple']);
        $this->assertSame('PRIMARY EXHIBIT DOSSIER', $singleSummary['dossierBadgeText']);
        $this->assertSame('Primary Suspect Seizures & Exhibits — Solo Suspect', $singleSummary['dossierHeadingText']);

        $multiSummary = $this->resolver->resolveFinalJointExhibitsSummary(
            $singleCase,
            $allExhibits,
            [],
            ['kwame asante', 'kofi mensah'],
            'Kwame Asante & Kofi Mensah',
            true,
            true,
            [['suspect_name' => 'Kwame Asante'], ['suspect_name' => 'Kofi Mensah']],
            [],
        );

        $this->assertTrue($multiSummary['isMultiple']);
        $this->assertSame('PRIMARY & CO-SUSPECT EXHIBIT DOSSIER', $multiSummary['dossierBadgeText']);
        $this->assertSame(
            'Primary & Co-Suspect Seizures & Exhibits — Kwame Asante & Kofi Mensah',
            $multiSummary['dossierHeadingText'],
        );
    }
}

