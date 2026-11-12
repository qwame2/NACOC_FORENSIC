<?php

namespace Tests\Feature;

use App\Models\DrugScreening;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\LaboratoryExaminationResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowReviewUnitParityTest extends TestCase
{
    use RefreshDatabase;

    protected function createUnitUser(string $unit, bool $isHead = false): User
    {
        return User::factory()->create([
            'fullname' => "Officer of {$unit}",
            'unit' => $unit,
            'is_head' => $isHead,
            'is_approved' => true,
            'is_staff' => true,
        ]);
    }

    #[Test]
    public function suspect_details_and_case_information_have_complete_parity_across_all_units_show_review(): void
    {
        // 1. Create a rich case with full case info, suspect booking, exhibits, and lab exam findings
        $case = ForensicCase::create([
            'case_number' => 'NACOC-2026-PARITY-001',
            'title' => 'Operation Gold Falcon Parity Test',
            'operation_code_name' => 'Operation Gold Falcon',
            'operation_code' => 'OP-FALCON',
            'laboratory_number' => 'LAB-2026-PARITY-999',
            'description_of_exhibits' => 'Consignment of 3 taped rectangular parcels recovered from concealment vehicle compartment',
            'suspect_name' => 'Kwame Mensah',
            'gender' => 'Male',
            'age' => 38,
            'nationality' => 'Ghanaian',
            'address' => 'Plot 45 Spintex Road, Accra',
            'marital_status' => 'Married',
            'spouse_name' => 'Akosua Mensah',
            'spouse_phone' => '0244123456',
            'dependents' => '3 Children',
            'raid_location' => 'Tema Harbour Checkpoint 2',
            'date_of_arrest' => '2026-11-01',
            'holding_facility' => 'NACOC Headquarters Detention Facility',
            'offense_description' => 'Unlawful Possession of Narcotic Drugs under Section 37',
            'has_seizures' => true,
            'exhibit_category' => 'Drugs',
            'seizure_name_drug' => 'White Compressed Substance',
            'commodity_name' => 'White Compressed Substance',
            'seizure_quantity' => '3',
            'seizure_packaging' => 'Brown Cellotape Blocks',
            'seizure_weight' => '3.45',
            'seizure_unit' => 'kg',
            'unit_name' => 'Crime Scene Support (CSSU)',
        ]);

        // 2. Drug screening record
        DrugScreening::create([
            'civilian_name' => 'Kwame Mensah',
            'purpose' => 'Pre-custody intake drug screening',
            'result' => 'Negative',
            'screening_date' => now(),
        ]);

        // 3. Persisted exhibit with new fields
        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'forensic_case_id' => $case->id,
            'case_id' => $case->id,
            'case_number' => $case->case_number,
            'exhibit_number' => 'EXH-2026-00001',
            'lab_exhibit_no' => 'LAB-EXH-01-A',
            'commodity_name' => 'White Compressed Substance',
            'category' => 'Drugs',
            'contents' => 'Off-white chalky dense powder',
            'source_of_exhibits' => 'Vehicle Secret Compartment',
            'source_requesting_unit' => 'Enforcement and Operations Unit',
            'investigating_officer' => 'Inspector Kofi Annan',
            'quantity' => '3',
            'weight' => '3.45',
            'unit' => 'kg',
            'packaging' => 'Brown Cellotape Blocks',
            'suspect_name' => 'Kwame Mensah',
            'associated_person' => 'Kwame Mensah',
        ]);

        // 4. Persisted laboratory examination result
        LaboratoryExaminationResult::create([
            'single_suspect_case_id' => $case->id,
            'suspect_name' => 'Kwame Mensah',
            'laboratory_number' => 'LAB-2026-PARITY-999',
            'exam_reference' => 'NLR-2026-00042',
            'exhibit_number' => 'EXH-2026-00001',
            'lab_exhibit_no' => 'LAB-EXH-01-A',
            'analyst' => 'Dr. Emmanuel Quaye',
            'date_received' => '2026-11-02',
            'date_started' => '2026-11-03',
            'date_completed' => '2026-11-05',
            'purpose_of_examination' => 'Identification and chemical purity quantification of suspected cocaine substance',
            'preliminary_exam_procedure' => 'Cobalt Thiocyanate colorimetric presumptive spot assay',
            'screening_method' => json_encode(['Gas Chromatography-Mass Spectrometry (GC-MS)', 'FTIR Spectrometry']),
            'disposition_of_exhibit' => 'Retained in evidence vault pending court trial',
            'substance_identification' => 'Cocaine Hydrochloride',
            'presumptive_result' => 'YES (Presence of: Cocaine Hydrochloride; Purity/Percentage: 84.5%)',
            'confirmatory_result' => 'YES',
            'sample_weight_description' => 'Fine white crystalline powder',
            'sample_weight_gross' => '3.45 kg',
            'sample_weight_units' => '3 Blocks',
            'sample_weight_taken' => '2.5 g',
            'sample_weight_reserved' => '3.4475 kg',
            'test_methods' => 'ASTM E2329 Standard Practice for Identification of Seized Drugs',
            'specifications' => 'Certified Reference Standard Cocaine HCl Lot #9982',
            'remark' => 'High purity narcotic contraband verified',
            'findings' => 'Chemical and instrumental analyses conclusively confirmed the presence of Cocaine Hydrochloride at 84.5% purity.',
            'has_saved' => true,
        ]);

        $unitsToTest = [
            'Crime Scene Support' => [
                'unit' => 'Crime Scene Support (CSSU)',
                'url' => "/Crime_Scene_Support/cases/{$case->case_number}/review",
                'is_head' => false,
            ],
            'Digital & Physical Forensics' => [
                'unit' => 'Digital & Physical Forensics (DPFU)',
                'url' => "/Digital_Forensics/cases/{$case->case_number}/review",
                'is_head' => false,
            ],
            'Evidence Management' => [
                'unit' => 'Evidence Management (EMU)',
                'url' => "/Evidence_Management/cases/{$case->case_number}/review",
                'is_head' => false,
            ],
            'Forensic Head' => [
                'unit' => 'Forensic Directorate (HOF)',
                'url' => "/Forensic_Head/cases/{$case->case_number}/review",
                'is_head' => true,
            ],
            'Lab Analysis' => [
                'unit' => 'Laboratory Analysis (LAU)',
                'url' => "/Lab_Analysis/cases/{$case->case_number}/review",
                'is_head' => false,
            ],
        ];

        foreach ($unitsToTest as $unitLabel => $meta) {
            $user = $this->createUnitUser($meta['unit'], $meta['is_head']);
            $response = $this->actingAs($user)->get($meta['url']);

            $this->assertEquals(200, $response->getStatusCode(), "Unit show review failed for {$unitLabel} at {$meta['url']}");
            $html = $response->getContent();

            // 1. Case information parity
            $this->assertStringContainsString('Operation Gold Falcon Parity Test', $html, "Case title missing in {$unitLabel}");
            $this->assertStringContainsString('NACOC-2026-PARITY-001', $html, "Case number missing in {$unitLabel}");
            $this->assertStringContainsString('Operation Gold Falcon', $html, "Operation type missing in {$unitLabel}");
            $this->assertStringContainsString('LAB-2026-PARITY-999', $html, "Laboratory number missing in Section 1 in {$unitLabel}");
            $this->assertStringContainsString('Consignment of 3 taped rectangular parcels recovered from concealment vehicle compartment', $html, "Description of exhibits missing in Section 1 in {$unitLabel}");

            // 2. Suspect details parity
            $this->assertStringContainsString('Kwame Mensah', $html, "Suspect name missing in {$unitLabel}");
            $this->assertStringContainsString('Plot 45 Spintex Road, Accra', $html, "Residential address missing in {$unitLabel}");
            $this->assertStringContainsString('Akosua Mensah', $html, "Spouse name missing in {$unitLabel}");
            $this->assertStringContainsString('0244123456', $html, "Spouse phone missing in {$unitLabel}");
            $this->assertStringContainsString('3 Children', $html, "Dependents missing in {$unitLabel}");
            $this->assertStringContainsString('Tema Harbour Checkpoint 2', $html, "Raid location missing in {$unitLabel}");
            $this->assertStringContainsString('NACOC Headquarters Detention Facility', $html, "Custody facility missing in {$unitLabel}");
            $this->assertStringContainsString('Unlawful Possession of Narcotic Drugs under Section 37', $html, "Offence charges missing in {$unitLabel}");

            // 3. Exhibits table parity (contents, lab exhibit no, IO, source requesting unit)
            $this->assertStringContainsString('White Compressed Substance', $html, "Commodity name missing in {$unitLabel}");
            $this->assertStringContainsString('Off-white chalky dense powder', $html, "Exhibit visual contents missing in {$unitLabel}");
            $this->assertStringContainsString('LAB-EXH-01-A', $html, "Lab exhibit number missing in {$unitLabel}");
            $this->assertStringContainsString('Inspector Kofi Annan', $html, "Investigating officer missing in {$unitLabel}");
            $this->assertStringContainsString('Enforcement and Operations Unit', $html, "Source requesting unit missing in {$unitLabel}");

            // 4. Lab examination card parity on all units
            $this->assertStringContainsString('Identification and chemical purity quantification of suspected cocaine substance', $html, "Purpose of examination missing in {$unitLabel}");
            $this->assertStringContainsString('Cobalt Thiocyanate colorimetric presumptive spot assay', $html, "Preliminary exam procedure missing in {$unitLabel}");
            $this->assertStringContainsString('Gas Chromatography-Mass Spectrometry (GC-MS)', $html, "Screening method missing in {$unitLabel}");
            $this->assertStringContainsString('Retained in evidence vault pending court trial', $html, "Disposition of exhibit missing in {$unitLabel}");
            $this->assertStringContainsString('Cocaine Hydrochloride', $html, "Substance identified missing in {$unitLabel}");
            $this->assertStringContainsString('Dr. Emmanuel Quaye', $html, "Analyst missing in {$unitLabel}");
            $this->assertStringContainsString('ASTM E2329 Standard Practice for Identification of Seized Drugs', $html, "Test methods missing in {$unitLabel}");

            // 5. System-generated Preliminary Exhibit Analysis Report attached to suspect across all units
            $this->assertStringContainsString('Attached Report Files', $html, "Attached report files container missing in {$unitLabel}");
            $this->assertStringContainsString('PRELIM', $html, "PRELIM badge missing in {$unitLabel}");
            $this->assertStringContainsString('Preliminary_Report_NLR-2026-00042.pdf', $html, "Preliminary report filename missing in {$unitLabel}");
            $this->assertStringContainsString('Lab_Analysis/preliminary-report-pdf/NACOC-2026-PARITY-001', $html, "Preliminary report PDF link missing in {$unitLabel}");

            // 6. Unit boundaries: Only Lab Analysis may render the interactive Section 5 form
            if ($unitLabel !== 'Lab Analysis') {
                $this->assertStringNotContainsString('id="section5LabExamination"', $html, "Non-lab unit {$unitLabel} must not render interactive section5LabExamination form");
            }
        }
    }

    #[Test]
    public function preliminary_exhibit_report_is_attached_to_all_suspects_in_multiple_suspect_lab_case_across_all_units_show_review(): void
    {
        $multiCase = \App\Models\MultipleSuspectCase::create([
            'case_number' => 'NACOC-2026-MULTI-PARITY-002',
            'title' => 'Operation Ocean Tide Multi Suspect Lab Case',
            'operation_code_name' => 'Operation Ocean Tide',
            'laboratory_number' => 'LAB-2026-MULTI-002',
            'description_of_exhibits' => 'Compressed herbal cannabis blocks concealed in shipping crates',
            'unit_name' => 'Laboratory Analysis (LAU)',
        ]);

        $suspect1 = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kofi Mensah',
            'role' => 'Primary Suspect',
            'gender' => 'Male',
            'age' => 42,
            'nationality' => 'Ghanaian',
            'raid_location' => 'Takoradi Port Container Yard',
            'holding_facility' => 'Sekondi Central Detention Center',
        ]);

        $suspect2 = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Kwaku Boateng',
            'role' => 'Co-Suspect',
            'gender' => 'Male',
            'age' => 36,
            'nationality' => 'Ghanaian',
            'raid_location' => 'Takoradi Port Container Yard',
            'holding_facility' => 'Sekondi Central Detention Center',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'forensic_case_id' => $multiCase->id,
            'case_number' => $multiCase->case_number,
            'exhibit_number' => 'EXH-2026-MULTI-01',
            'lab_exhibit_no' => 'LAB-EXH-MULTI-01',
            'commodity_name' => 'Compressed Herbal Plant Material',
            'category' => 'Drugs',
            'contents' => 'Greenish-brown dry compressed vegetative plant material',
            'source_of_exhibits' => 'Crate #4 Container MEDU8823',
            'source_requesting_unit' => 'Western Regional Command',
            'investigating_officer' => 'Chief Inspector Osei',
            'quantity' => '20',
            'weight' => '25.0',
            'unit' => 'kg',
            'associated_person' => 'All Suspects',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'laboratory_number' => 'LAB-2026-MULTI-002',
            'exam_reference' => 'NLA-FM-T0088-F',
            'exhibit_number' => 'EXH-2026-MULTI-01',
            'lab_exhibit_no' => 'LAB-EXH-MULTI-01',
            'commodity_name' => 'Compressed Herbal Plant Material',
            'purpose_of_examination' => 'Presumptive and definitive chemical analysis of narcotic substance',
            'preliminary_exam_procedure' => 'Sampling representative core portions and performing sequential Fast Blue B and Duquenois-Levine colorimetric spot tests',
            'screening_method' => 'Fast Blue B Reagent & Duquenois-Levine Colorimetric Spot Test',
            'presumptive_result' => 'POSITIVE (Cannabis Sativa)',
            'confirmatory_result' => 'Confirmed Cannabis Sativa with Delta-9-THC',
            'sample_weight_gross' => '25.0 kg',
            'sample_weight_taken' => '10.0 g',
            'sample_weight_reserved' => '24.99 kg',
            'test_methods' => 'Fast Blue B Colorimetric Chemical Spot Test',
            'specifications' => 'Certified Cannabis Reference Standard',
            'findings' => 'Presumptive and confirmatory testing confirmed Cannabis Sativa',
            'disposition_of_exhibit' => 'Securely stored in NACOC Western Vault',
            'analyst' => 'Dr. Emmanuel Quaye',
            'has_saved' => true,
        ]);

        $unitsToTest = [
            'Crime Scene Support' => [
                'unit' => 'Crime Scene Support (CSSU)',
                'url' => "/Crime_Scene_Support/cases/{$multiCase->case_number}/review",
                'is_head' => false,
            ],
            'Digital & Physical Forensics' => [
                'unit' => 'Digital & Physical Forensics (DPFU)',
                'url' => "/Digital_Forensics/cases/{$multiCase->case_number}/review",
                'is_head' => false,
            ],
            'Evidence Management' => [
                'unit' => 'Evidence Management (EMU)',
                'url' => "/Evidence_Management/cases/{$multiCase->case_number}/review",
                'is_head' => false,
            ],
            'Forensic Head' => [
                'unit' => 'Forensic Directorate (HOF)',
                'url' => "/Forensic_Head/cases/{$multiCase->case_number}/review",
                'is_head' => true,
            ],
            'Lab Analysis' => [
                'unit' => 'Laboratory Analysis (LAU)',
                'url' => "/Lab_Analysis/cases/{$multiCase->case_number}/review",
                'is_head' => false,
            ],
        ];

        foreach ($unitsToTest as $unitLabel => $meta) {
            $user = $this->createUnitUser($meta['unit'], $meta['is_head']);
            $response = $this->actingAs($user)->get($meta['url']);

            $this->assertEquals(200, $response->getStatusCode(), "Unit show review failed for {$unitLabel} at {$meta['url']}");
            $html = $response->getContent();

            // Verify preliminary exhibit report card attached on all units
            $this->assertStringContainsString('Attached Report Files', $html, "Attached report files container missing in {$unitLabel}");
            $this->assertStringContainsString('PRELIM', $html, "PRELIM badge missing in {$unitLabel}");
            $this->assertStringContainsString('Preliminary_Report_NLA-FM-T0088-F.pdf', $html, "Preliminary report filename missing in {$unitLabel}");
            $this->assertStringContainsString('Lab_Analysis/preliminary-report-pdf/NACOC-2026-MULTI-PARITY-002', $html, "Preliminary report PDF link missing in {$unitLabel}");

            // Verify both suspects are rendered
            $this->assertStringContainsString('Kofi Mensah', $html, "Primary suspect missing in {$unitLabel}");
            $this->assertStringContainsString('Kwaku Boateng', $html, "Co-suspect missing in {$unitLabel}");
        }
    }

    #[Test]
    public function preliminary_report_is_not_attached_to_suspects_with_no_drug_exhibits_on_show_review(): void
    {
        $multiCase = \App\Models\MultipleSuspectCase::create([
            'case_number' => 'NACOC-2026-MULTI-SELECTIVE-003',
            'title' => 'Operation Selective Exhibit Drug vs Non-Drug',
            'operation_code_name' => 'Operation Selective Exhibit',
            'laboratory_number' => 'LAB-2026-MULTI-003',
            'unit_name' => 'Laboratory Analysis (LAU)',
        ]);

        // Suspect 1 has drugs
        $drugSuspect = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Yaw Asante',
            'role' => 'Primary Suspect',
            'gender' => 'Male',
        ]);

        // Suspect 2 has ONLY electronics (no drugs)
        $nonDrugSuspect = \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Ama Serwaa',
            'role' => 'Co-Suspect',
            'gender' => 'Female',
        ]);

        // Drug exhibit belonging ONLY to Yaw Asante
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'forensic_case_id' => $multiCase->id,
            'case_number' => $multiCase->case_number,
            'case_suspect_id' => $drugSuspect->id,
            'suspect_id' => $drugSuspect->id,
            'associated_person' => 'Yaw Asante',
            'exhibit_number' => 'EXH-DRUG-001',
            'lab_exhibit_no' => 'LAB-DRUG-001',
            'commodity_name' => 'Cocaine Powder',
            'category' => 'Drugs',
            'contents' => 'White dense compressed powder',
            'quantity' => '1',
            'weight' => '1.5',
            'unit' => 'kg',
        ]);

        // Electronic exhibit belonging ONLY to Ama Serwaa
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'forensic_case_id' => $multiCase->id,
            'case_number' => $multiCase->case_number,
            'case_suspect_id' => $nonDrugSuspect->id,
            'suspect_id' => $nonDrugSuspect->id,
            'associated_person' => 'Ama Serwaa',
            'exhibit_number' => 'EXH-ELEC-002',
            'commodity_name' => 'iPhone 14 Pro',
            'category' => 'Electronic Devices',
            'contents' => 'Smartphone used for communication',
        ]);

        LaboratoryExaminationResult::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $drugSuspect->id,
            'laboratory_number' => 'LAB-2026-MULTI-003',
            'exam_reference' => 'NLA-FM-T0099-F',
            'exhibit_number' => 'EXH-DRUG-001',
            'presumptive_result' => 'POSITIVE (Cocaine)',
            'has_saved' => true,
        ]);

        $user = $this->createUnitUser('Laboratory Analysis (LAU)', false);
        $response = $this->actingAs($user)->get("/Lab_Analysis/cases/{$multiCase->case_number}/review");
        $this->assertEquals(200, $response->getStatusCode());

        $html = $response->getContent();

        // Yaw Asante (Drug Suspect) dossier must have the preliminary report
        $this->assertStringContainsString('Yaw Asante', $html);
        $this->assertStringContainsString('Preliminary_Report_NLA-FM-T0099-F.pdf', $html);

        // Ama Serwaa (Electronic/Non-Drug Suspect) dossier must NOT have preliminary report
        $this->assertStringContainsString('Ama Serwaa', $html);

        // Verify that Ama Serwaa's dossier card does NOT render PRELIM report
        // Find Ama Serwaa section
        $amaPos = strpos($html, 'Ama Serwaa');
        $this->assertNotFalse($amaPos);
        $amaSection = substr($html, $amaPos, 3000);
        $this->assertStringNotContainsString('PRELIM', $amaSection, 'Suspect with no drug exhibits must not have Preliminary_Report attached.');
    }
}
