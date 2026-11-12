<?php

namespace Tests\Unit\Services;

use App\Services\DrugExhibitClassifier;
use PHPUnit\Framework\TestCase;

class DrugExhibitClassifierTest extends TestCase
{
    protected DrugExhibitClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new DrugExhibitClassifier();
    }

    public function test_classifies_explicit_drug_and_narcotic_exhibits_as_true(): void
    {
        $this->assertTrue($this->classifier->isDrugExhibit([
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Hydrochloride',
        ]));

        $this->assertTrue($this->classifier->isDrugExhibit([
            'category' => 'Suspected Narcotic Substance',
        ]));

        $this->assertTrue($this->classifier->isDrugExhibit([
            'exhibit_category' => 'General Exhibit',
            'seizure_name_drug' => 'Heroin',
        ]));
    }

    public function test_classifies_non_drug_exhibits_as_false(): void
    {
        $this->assertFalse($this->classifier->isDrugExhibit(null));
        $this->assertFalse($this->classifier->isDrugExhibit([]));

        $this->assertFalse($this->classifier->isDrugExhibit([
            'exhibit_category' => 'Non-Drug Exhibit',
            'exhibit_type' => 'Drugs',
        ]));

        $this->assertFalse($this->classifier->isDrugExhibit([
            'exhibit_category' => 'Electronic Device / Phone',
            'commodity_name' => 'iPhone 15 Pro',
        ]));

        $this->assertFalse($this->classifier->isDrugExhibit([
            'exhibit_category' => 'Vehicle',
            'commodity_name' => 'Toyota Hilux',
        ]));

        $this->assertFalse($this->classifier->isDrugExhibit([
            'exhibit_category' => 'Weapon',
            'seizure_name_drug' => 'N/A',
        ]));
    }

    public function test_classify_case_detects_primary_and_any_drug_case_and_respects_digital_forensics_flag(): void
    {
        $drugCase = (object) [
            'exhibit_category' => 'Drugs',
            'exhibit_type' => 'Drugs',
            'seizure_name_drug' => 'Cannabis',
            'seizure_name' => 'Cannabis',
            'exhibits_summary' => '2 slabs',
            'offense' => 'Possession',
        ];
        $docketWithDrug = [
            'seizures' => [
                [
                    'exhibit_category' => 'Drugs',
                    'seizure_name_drug' => 'Cannabis',
                    'seizure_quantity' => '2 slabs',
                    'seizure_weight' => '200',
                    'seizure_unit' => 'g',
                ],
            ],
        ];

        $result = $this->classifier->classifyCase($drugCase, $docketWithDrug, false);
        $this->assertTrue($result['isDrugCase']);
        $this->assertTrue($result['isPrimaryDrugCase']);
        $this->assertTrue($result['isAnyDrugCase']);
        $this->assertCount(1, $result['topDrugExhibits']);

        $digitalOnlyCase = (object) [
            'exhibit_category' => 'Digital Forensics',
            'exhibit_type' => 'Phone',
            'seizure_name_drug' => null,
            'seizure_name' => 'Samsung Galaxy',
        ];
        $digitalDocket = [
            'seizures' => [
                [
                    'exhibit_category' => 'Electronic Device',
                    'seizure_name' => 'Samsung Galaxy',
                ],
            ],
        ];

        $digResult = $this->classifier->classifyCase($digitalOnlyCase, $digitalDocket, true);
        $this->assertFalse($digResult['isDrugCase']);
        $this->assertFalse($digResult['isPrimaryDrugCase']);
        $this->assertEmpty($digResult['topDrugExhibits']);
    }

    public function test_classify_suspect_exhibits_separates_drug_electronic_and_crime_scene_domains(): void
    {
        $exhibits = [
            ['exhibit_category' => 'Drugs', 'seizure_name_drug' => 'Cocaine'],
            ['exhibit_category' => 'Digital Device', 'commodity_name' => 'Laptop'],
            ['exhibit_category' => 'Vehicle', 'commodity_name' => 'Honda Civic'],
        ];

        $flags = $this->classifier->classifySuspectExhibits($exhibits);
        $this->assertTrue($flags['suspectHasDrug']);
        $this->assertTrue($flags['suspectHasElectronic']);
        $this->assertTrue($flags['suspectHasCrimeScene']);
        $this->assertTrue($flags['activeSuspectHasDrugExhibit']);
        $this->assertTrue($flags['hasAnySpecialistUnit']);
    }

    public function test_recognizes_extended_controlled_substances_and_handles_closures(): void
    {
        $closure = $this->classifier->getClosure();

        $this->assertTrue($closure([
            'commodity_name' => 'Tapentadol 100mg Tablets',
        ]));

        $this->assertTrue($closure([
            'commodity_name' => 'Pregabalin Capsules',
        ]));

        $this->assertTrue($closure([
            'commodity_name' => 'Ketamine Hydrochloride',
        ]));

        $this->assertTrue($closure([
            'substance_identification' => 'Diazepam',
        ]));

        $this->assertSame('Tapentadol', DrugExhibitClassifier::extractKnownSubstance('Seized 5 blisters of Tapentadol'));
        $this->assertSame('Ketamine', DrugExhibitClassifier::extractKnownSubstance('Ketamine powder in sachet'));
    }
}

