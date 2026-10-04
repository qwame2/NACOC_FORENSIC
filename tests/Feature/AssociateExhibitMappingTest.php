<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\LaboratoryExaminationResult;
use App\Models\AssociateLabExamination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssociateExhibitMappingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function associate_suspect_with_drug_exhibit_displays_drug_and_not_primary_weapon()
    {
        $headUser = User::factory()->head()->create([
            'fullname' => 'Commanding Officer Alpha',
            'unit' => 'Crime Scene Support',
        ]);

        // Create multi-suspect case CSS-2026-9425
        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-9425',
            'title' => 'Republic vs Primary Suspect & Wakanda Asiedu',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $headUser->id,
            'case_status' => 'Under Investigation',
        ]);

        // Suspect 1: Primary Suspect
        $primarySuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Primary Suspect',
            'role' => 'Primary Suspect',
        ]);

        // Suspect 2: Wakanda Asiedu (Associate Suspect)
        $wakandaSuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Wakanda Asiedu',
            'role' => 'Associate Suspect',
            'associate_id' => 'ASC-001',
        ]);

        // Exhibit 1: Weapon belonging to Primary Suspect
        $weaponExhibit = Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $primarySuspect->id,
            'exhibit_number' => '1',
            'exhibit_category' => 'Weapon / Firearm',
            'commodity_name' => 'AK-47 Rifle',
            'wpn_model' => 'AK-47',
            'wpn_serial_no' => 'AK-998822',
        ]);

        // Exhibit 2: Drug belonging to Wakanda Asiedu
        $drugExhibit = Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $wakandaSuspect->id,
            'exhibit_number' => '2',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine Powder (500g)',
            'seizure_quantity' => '1',
            'seizure_weight' => '500',
            'seizure_unit' => 'g',
        ]);

        // Wrap as ForensicCase
        $case = ForensicCase::where('case_number', 'CSS-2026-9425')->first();
        $this->assertNotNull($case);

        $wakandaExhibits = $case->getExhibitsForSuspect('Wakanda Asiedu', $wakandaSuspect->id, 'ASC-001', 'Associate Suspect');

        // Assert Wakanda has 1 exhibit and it is Drugs, NOT Weapon
        $this->assertCount(1, $wakandaExhibits);
        $firstEx = (array) reset($wakandaExhibits);
        $this->assertEquals('Drugs', $firstEx['exhibit_category'] ?? $firstEx['category']);
        $this->assertEquals('Cocaine Powder (500g)', $firstEx['commodity_name'] ?? $firstEx['seizure_name']);

        // Assert Primary Suspect has 1 exhibit and it is Weapon
        $primaryExhibits = $case->getExhibitsForSuspect('Primary Suspect', $primarySuspect->id, 'PRIM-001', 'Primary Suspect');
        $this->assertCount(1, $primaryExhibits);
        $pEx = (array) reset($primaryExhibits);
        $this->assertEquals('Weapon / Firearm', $pEx['exhibit_category'] ?? $pEx['category']);

        // Test review HTTP endpoint rendering
        $response = $this->actingAs($headUser)
            ->get("/cases/{$case->case_number}/review?from=Lab%20Analysis");

        $response->assertStatus(200);
        
        // Verify in rendered HTML that Wakanda Asiedu's Associate Dossier section shows Drugs / Cocaine Powder
        $content = $response->getContent();
        $this->assertStringContainsString('Wakanda Asiedu', $content);
        $this->assertStringContainsString('Cocaine Powder', $content);
    }

    #[Test]
    public function shared_exhibits_are_displayed_for_both_linked_suspects_but_not_unlinked_suspects()
    {
        $headUser = User::factory()->head()->create();

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-SHARED1',
            'title' => 'Republic vs Eric Addo, Linda Atta & Kofi Badu',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $headUser->id,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Eric Addo',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Linda Atta',
            'role' => 'Associate Suspect',
            'associate_id' => 'ASC-001',
        ]);

        $suspect3 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 3,
            'suspect_name' => 'Kofi Badu',
            'role' => 'Associate Suspect',
            'associate_id' => 'ASC-002',
        ]);

        // Shared Drug exhibit between Eric Addo and Linda Atta
        $sharedDrug = Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'exhibit_number' => 'EX-001',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin Slabs (2kg)',
            'veh_owner' => 'Eric Addo, Linda Atta', // Joint ownership
        ]);

        // Weapon belonging ONLY to Kofi Badu
        $kofiWeapon = Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect3->id,
            'exhibit_number' => 'EX-002',
            'exhibit_category' => 'Weapon / Firearm',
            'commodity_name' => 'Pistol 9mm',
            'wpn_model' => 'Glock 17',
        ]);

        $case = ForensicCase::where('case_number', 'CSS-2026-SHARED1')->first();

        // Linda Atta should see Heroin Slabs, NOT Pistol 9mm
        $lindaExhibits = $case->getExhibitsForSuspect('Linda Atta', $suspect2->id, 'ASC-001', 'Associate Suspect');
        $this->assertCount(1, $lindaExhibits);
        $lEx = (array) reset($lindaExhibits);
        $this->assertEquals('Heroin Slabs (2kg)', $lEx['commodity_name'] ?? $lEx['seizure_name']);

        // Kofi Badu should see Pistol 9mm, NOT Heroin Slabs
        $kofiExhibits = $case->getExhibitsForSuspect('Kofi Badu', $suspect3->id, 'ASC-002', 'Associate Suspect');
        if (count($kofiExhibits) !== 1) {
            fwrite(STDERR, "DEBUG KOFI EXHIBITS: " . print_r($kofiExhibits, true) . "\n");
        }
        $this->assertCount(1, $kofiExhibits);
        $kEx = (array) reset($kofiExhibits);
        $this->assertEquals('Pistol 9mm', $kEx['commodity_name'] ?? $kEx['seizure_name']);
    }

    #[Test]
    public function suspect_without_drug_exhibit_does_not_appear_on_lab_result_page()
    {
        $headUser = User::factory()->head()->create();

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-NODRUG1',
            'title' => 'Republic vs Primary Weapon Suspect & Drug Associate',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $headUser->id,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Weapon Only Suspect',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Drug Associate Suspect',
            'role' => 'Associate Suspect',
            'associate_id' => 'ASC-001',
        ]);

        // Exhibit 1: Weapon belonging to Weapon Only Suspect
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect1->id,
            'exhibit_number' => 'W-001',
            'exhibit_category' => 'Weapon / Firearm',
            'commodity_name' => 'Shotgun',
        ]);

        // Exhibit 2: Drug belonging to Drug Associate Suspect
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect2->id,
            'exhibit_number' => 'D-001',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Methamphetamine Crystals (100g)',
        ]);

        // 1. Check lab result page endpoint (/lab-result)
        $response = $this->actingAs($headUser)->get('/lab-result');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Drug Associate Suspect should appear on lab result page under Methamphetamine
        $this->assertStringContainsString('Drug Associate Suspect', $content);
        $this->assertStringContainsString('Methamphetamine Crystals', $content);

        // Weapon Only Suspect must NOT appear on the lab result page
        $this->assertStringNotContainsString('Weapon Only Suspect', $content);

        // 2. Check analytical report view (/Lab_Analysis/analytical/CSS-2026-NODRUG1)
        $analyticalResp = $this->actingAs($headUser)->get('/Lab_Analysis/analytical/CSS-2026-NODRUG1');
        $analyticalResp->assertStatus(200);
        $analyticalContent = $analyticalResp->getContent();

        $this->assertStringContainsString('Drug Associate Suspect', $analyticalContent);
        $this->assertStringNotContainsString('Weapon Only Suspect', $analyticalContent);
    }

    #[Test]
    public function chris_addo_without_drug_exhibit_does_not_appear_on_lab_result_page()
    {
        $headUser = User::factory()->head()->create();

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-CHRIS1',
            'title' => 'Republic vs Primary Suspect & Chris Addo',
            'unit_name' => 'Crime Scene Support',
            'controller_user_id' => $headUser->id,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Primary Drug Suspect',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Chris Addo',
            'role' => 'Co-Suspect',
        ]);

        // Drug Exhibit belonging ONLY to Primary Drug Suspect
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect1->id,
            'exhibit_number' => 'EX-CHRIS-01',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin Powder (1kg)',
        ]);

        // Non-drug exhibit belonging to Chris Addo (e.g. Vehicle)
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_id' => $suspect2->id,
            'exhibit_number' => 'EX-CHRIS-02',
            'exhibit_category' => 'Vehicles',
            'commodity_name' => 'Truck Vehicle',
        ]);

        $response = $this->actingAs($headUser)->get('/lab-result');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Primary Drug Suspect', $content);
        $this->assertStringNotContainsString('Chris Addo', $content);

        $analyticalResp = $this->actingAs($headUser)->get('/Lab_Analysis/analytical/CSS-2026-CHRIS1');
        $analyticalResp->assertStatus(200);
        $analyticalContent = $analyticalResp->getContent();

        $this->assertStringContainsString('Primary Drug Suspect', $analyticalContent);
        $this->assertStringNotContainsString('Chris Addo', $analyticalContent);
    }

    #[Test]
    public function electronic_device_forensic_specifications_are_saved_and_displayed_in_review_dossier()
    {
        $digUser = User::factory()->create([
            'fullname' => 'Inspector Digital',
            'unit' => 'Digital & Physical Forensics',
        ]);

        $mCase = MultipleSuspectCase::create([
            'case_number' => 'DIG-2026-8800',
            'title' => 'Republic vs Suspect Tech & Cyber Associate',
            'unit_name' => 'Digital & Physical Forensics',
            'controller_user_id' => $digUser->id,
            'case_status' => 'Under Investigation',
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $mCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Cyber Suspect',
            'role' => 'Primary Suspect',
        ]);

        $postData = [
            'associated_exhibits' => [
                [
                    'associated_person' => 'Cyber Suspect',
                    'suspect_name' => 'Cyber Suspect',
                    'category' => 'Electronic Devices',
                    'exhibit_category' => 'Electronic Devices',
                    'seizure_name' => 'Encrypted Smartphone',
                    'dev_model' => 'iPhone 15 Pro Max',
                    'dev_serial_no' => 'F2LZX984K102',
                    'dev_imei1' => '356938035643809',
                    'dev_imei2' => '356938035643817',
                    'dev_mac_address' => '00:1A:2B:3C:4D:5E',
                    'dev_storage' => '256 GB',
                    'dev_colour' => 'Titanium Gray',
                    'dev_marks' => 'Cracked rear glass screen',
                ]
            ]
        ];

        $saveResponse = $this->actingAs($digUser)
            ->postJson("/cases/{$mCase->id}/associate", $postData);

        $saveResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('exhibits', [
            'multiple_suspect_case_id' => $mCase->id,
            'dev_model' => 'iPhone 15 Pro Max',
            'dev_serial_no' => 'F2LZX984K102',
            'dev_imei1' => '356938035643809',
            'dev_imei2' => '356938035643817',
            'dev_mac_address' => '00:1A:2B:3C:4D:5E',
        ]);

        $reviewResponse = $this->actingAs($digUser)
            ->get("/cases/{$mCase->case_number}/review");

        $reviewResponse->assertStatus(200);
        $content = $reviewResponse->getContent();

        $this->assertStringContainsString('ELECTRONIC DEVICE FORENSIC SPECIFICATIONS', $content);
        $this->assertStringContainsString('iPhone 15 Pro Max', $content);
        $this->assertStringContainsString('F2LZX984K102', $content);
        $this->assertStringContainsString('356938035643809', $content);
        $this->assertStringContainsString('356938035643817', $content);
        $this->assertStringContainsString('00:1A:2B:3C:4D:5E', $content);
        $this->assertStringContainsString('256 GB', $content);
        $this->assertStringContainsString('Titanium Gray', $content);
        $this->assertStringContainsString('Cracked rear glass screen', $content);
    }
}
