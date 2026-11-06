<?php

namespace Tests\Unit\Services;

use App\Services\ExhibitOwnershipResolver;
use PHPUnit\Framework\TestCase;

class ExhibitOwnershipResolverTest extends TestCase
{
    protected ExhibitOwnershipResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ExhibitOwnershipResolver();
    }

    public function test_fuzzy_name_matching_handles_exact_substring_and_shared_surname(): void
    {
        $this->assertTrue($this->resolver->namesMatchFuzzy('Kwame Mensah', 'kwame mensah'));
        $this->assertTrue($this->resolver->namesMatchFuzzy('Kwame Kofi Mensah', 'Kwame Mensah'));
        $this->assertFalse($this->resolver->namesMatchFuzzy('Kwame Mensah', 'Abena Owusu'));
        $this->assertFalse($this->resolver->namesMatchFuzzy('', 'Kwame Mensah'));
    }

    public function test_detects_duplicate_primary_and_co_suspect_entries(): void
    {
        $primaryAndCo = ['kwame mensah', 'kofi boateng'];

        $this->assertTrue($this->resolver->isDuplicatePrimaryCoEntry('Kwame Mensah', $primaryAndCo));
        $this->assertTrue($this->resolver->isDuplicatePrimaryCoEntry('Kwame Mensah & Kofi Boateng', $primaryAndCo));
        $this->assertFalse($this->resolver->isDuplicatePrimaryCoEntry('Abena Owusu', $primaryAndCo));
    }

    public function test_identifies_joint_vs_primary_vs_associate_exhibits(): void
    {
        $primaryAndCo = ['Kwame Mensah', 'Kofi Boateng'];

        $this->assertTrue($this->resolver->isJointExhibit(['associated_person' => 'All Suspects'], $primaryAndCo));
        $this->assertTrue($this->resolver->isJointExhibit(['associated_person' => 'Joint Exhibit'], $primaryAndCo));
        $this->assertTrue($this->resolver->isJointExhibit(['associated_person' => 'Kwame Mensah & Kofi Boateng'], $primaryAndCo));
        $this->assertTrue($this->resolver->isJointExhibit(['associated_person' => 'Kwame Mensah, Kofi Boateng'], $primaryAndCo));

        $this->assertFalse($this->resolver->isJointExhibit(['associated_person' => 'Kwame Mensah'], $primaryAndCo));
        $this->assertFalse($this->resolver->isJointExhibit(['associated_person' => 'Abena Owusu'], $primaryAndCo));
        $this->assertFalse($this->resolver->isJointExhibit([], $primaryAndCo));
    }

    public function test_resolves_active_suspect_from_query_or_defaults_to_primary(): void
    {
        $case = (object) ['suspect_name' => 'Kwame Mensah', 'suspect_alias' => 'Boss'];
        $docketData = [
            'suspects' => [
                ['id' => 1, 'suspect_name' => 'Kwame Mensah', 'role' => 'Primary Suspect'],
                ['id' => 2, 'suspect_name' => 'Abena Owusu', 'role' => 'Associate Suspect'],
            ],
        ];

        $defaultRes = $this->resolver->resolveActiveSuspect($case, $docketData, null);
        $this->assertSame('Kwame Mensah', $defaultRes['activeSuspectName']);
        $this->assertTrue($defaultRes['isActiveSuspectPrimary']);

        $assocRes = $this->resolver->resolveActiveSuspect($case, $docketData, 'Abena Owusu');
        $this->assertSame('Abena Owusu', $assocRes['activeSuspectName']);
        $this->assertFalse($assocRes['isActiveSuspectPrimary']);
    }
}

