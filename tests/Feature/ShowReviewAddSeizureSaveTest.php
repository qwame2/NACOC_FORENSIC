<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\ForensicCase;
use App\Models\MultipleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShowReviewAddSeizureSaveTest extends TestCase
{
    use RefreshDatabase;

    protected function createOfficer(string $unit, bool $isHead = false): User
    {
        return User::factory()->create([
            'fullname' => "{$unit} Officer",
            'unit' => $unit,
            'is_head' => $isHead,
            'is_approved' => true,
            'is_staff' => true,
        ]);
    }

    #[Test]
    public function all_unit_show_and_staff_reviews_render_seizure_item_elements(): void
    {
        $units = [
            'Lab Analysis' => [
                'head_url' => '/Lab_Analysis/cases/CASE-SAV-001/review',
                'staff_url' => '/Lab_Analysis/staff/cases/CASE-SAV-001/review',
            ],
            'Crime Scene Support' => [
                'head_url' => '/Crime_Scene_Support/cases/CASE-SAV-001/review',
                'staff_url' => '/Crime_Scene_Support/staff/cases/CASE-SAV-001/review',
            ],
            'Digital Forensics' => [
                'head_url' => '/Digital_Forensics/cases/CASE-SAV-001/review',
                'staff_url' => '/Digital_Forensics/staff/cases/CASE-SAV-001/review',
            ],
            'Evidence Management' => [
                'head_url' => '/Evidence_Management/cases/CASE-SAV-001/review',
                'staff_url' => '/Evidence_Management/staff/cases/CASE-SAV-001/review',
            ],
            'Forensic Directorate' => [
                'head_url' => '/Forensic_Head/cases/CASE-SAV-001/review',
                'staff_url' => '/Forensic_Head/staff/cases/CASE-SAV-001/review',
            ],
        ];

        $case = MultipleSuspectCase::create([
            'case_number' => 'CASE-SAV-001',
            'title' => 'Republic v Test Seizure',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'total_suspects_count' => 1,
            'docket_data' => [
                'suspects' => [
                    ['id' => 1, 'suspect_name' => 'John Doe', 'association_role' => 'Primary Suspect'],
                ],
            ],
        ]);

        foreach ($units as $unitName => $routes) {
            // Head view
            $headUser = $this->createOfficer($unitName, isHead: true);
            $headResp = $this->actingAs($headUser)->get($routes['head_url']);
            $headResp->assertStatus(200);
            $headHtml = $headResp->getContent();

            $this->assertStringContainsString('id="seizureItemActivationCard"', $headHtml, "Head view {$unitName} missing seizureItemActivationCard");
            $this->assertStringContainsString('id="toggleSeizureItemSectionCheckbox"', $headHtml, "Head view {$unitName} missing toggleSeizureItemSectionCheckbox");
            $this->assertStringContainsString('id="section4Seizures"', $headHtml, "Head view {$unitName} missing section4Seizures");
            $this->assertStringContainsString('id="saveSection4SeizureBtn"', $headHtml, "Head view {$unitName} missing saveSection4SeizureBtn");
            $this->assertStringContainsString('id="seizureCountInput"', $headHtml, "Head view {$unitName} missing seizureCountInput");
            if ($unitName !== 'Digital Forensics') {
                $this->assertStringContainsString('id="manifestDescriptionOfExhibits"', $headHtml, "Head view {$unitName} missing manifestDescriptionOfExhibits");
            }
            $this->assertStringContainsString('function addSeizureBlock(', $headHtml, "Head view {$unitName} missing addSeizureBlock");
            $this->assertStringContainsString('function submitSection4SeizureForm(', $headHtml, "Head view {$unitName} missing submitSection4SeizureForm");

            // Staff view
            $staffUser = $this->createOfficer($unitName, isHead: false);
            $staffResp = $this->actingAs($staffUser)->get($routes['staff_url']);
            $staffResp->assertStatus(200);
            $staffHtml = $staffResp->getContent();

            $this->assertStringContainsString('id="seizureItemActivationCard"', $staffHtml, "Staff view {$unitName} missing seizureItemActivationCard");
            $this->assertStringContainsString('id="toggleSeizureItemSectionCheckbox"', $staffHtml, "Staff view {$unitName} missing toggleSeizureItemSectionCheckbox");
            $this->assertStringContainsString('id="section4Seizures"', $staffHtml, "Staff view {$unitName} missing section4Seizures");
            $this->assertStringContainsString('id="saveSection4SeizureBtn"', $staffHtml, "Staff view {$unitName} missing saveSection4SeizureBtn");
            $this->assertStringContainsString('id="seizureCountInput"', $staffHtml, "Staff view {$unitName} missing seizureCountInput");
            if ($unitName !== 'Digital Forensics') {
                $this->assertStringContainsString('id="manifestDescriptionOfExhibits"', $staffHtml, "Staff view {$unitName} missing manifestDescriptionOfExhibits");
            }
            $this->assertStringContainsString('function addSeizureBlock(', $staffHtml, "Staff view {$unitName} missing addSeizureBlock");
            $this->assertStringContainsString('function submitSection4SeizureForm(', $staffHtml, "Staff view {$unitName} missing submitSection4SeizureForm");
        }
    }

    #[Test]
    public function associate_endpoint_saves_added_seizure_exhibits_and_manifest_description(): void
    {
        $officer = $this->createOfficer('Lab Analysis');

        $case = MultipleSuspectCase::create([
            'case_number' => 'CASE-ASSOC-001',
            'title' => 'Republic v Suspect Exhibit Add',
            'unit_name' => 'Lab Analysis',
            'status' => 'Pending Approval',
            'user_id' => $officer->id,
            'total_suspects_count' => 1,
            'docket_data' => [
                'suspects' => [
                    ['id' => 1, 'suspect_name' => 'Kofi Annan', 'association_role' => 'Primary Suspect'],
                ],
            ],
        ]);

        $payload = [
            'associated_exhibits' => [
                [
                    'associated_person' => 'Kofi Annan',
                    'category' => 'Drugs',
                    'seizure_name' => 'Heroin Brown Powder',
                    'seizure_quantity' => '5',
                    'seizure_unit' => 'Kilograms',
                    'seizure_weight' => '5.2',
                ],
            ],
            'description_of_exhibits' => 'Five wrapped parcels of brown powdered substance.',
        ];

        $resp = $this->actingAs($officer)->postJson("/cases/{$case->id}/associate", $payload);
        $resp->assertStatus(200);
        $resp->assertJson(['success' => true]);

        $exhibit = Exhibit::where('multiple_suspect_case_id', $case->id)->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('Heroin Brown Powder', $exhibit->commodity_name);
        $this->assertEquals('Drugs', $exhibit->exhibit_category);

        $freshCase = $case->fresh();
        $this->assertEquals('Five wrapped parcels of brown powdered substance.', $freshCase->description_of_exhibits);
    }

    #[Test]
    public function electronic_device_exhibit_saved_in_section4_displays_full_details_on_digital_forensics_show_review(): void
    {
        $officer = $this->createOfficer('Digital Forensics', isHead: true);

        $case = MultipleSuspectCase::create([
            'case_number' => 'CASE-DEV-001',
            'title' => 'Republic v Suspect Device Test',
            'unit_name' => 'Digital Forensics',
            'status' => 'Pending Approval',
            'user_id' => $officer->id,
            'total_suspects_count' => 1,
            'docket_data' => [
                'suspects' => [
                    ['id' => 1, 'suspect_name' => 'Kwame Nkrumah', 'association_role' => 'Primary Suspect'],
                ],
            ],
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kwame Nkrumah',
        ]);

        $payload = [
            'associated_exhibits' => [
                [
                    'associated_person' => 'Kwame Nkrumah',
                    'category' => 'Electronic Devices',
                    'seizure_name' => 'iPhone 15 Pro Max',
                    'commodity_name' => 'iPhone 15 Pro Max',
                    'dev_model' => 'iPhone 15 Pro Max',
                    'dev_serial_no' => 'F2LZX984K102',
                    'dev_imei1' => '356938035643809',
                    'dev_imei2' => '356938035643817',
                    'dev_mac_address' => '00:1A:2B:3C:4D:5E',
                    'dev_storage' => '256 GB',
                    'dev_colour' => 'Space Gray',
                    'dev_marks' => 'Cracked screen',
                    'dev' => [
                        'model' => 'iPhone 15 Pro Max',
                        'serial_no' => 'F2LZX984K102',
                        'imei1' => '356938035643809',
                        'imei2' => '356938035643817',
                        'mac_address' => '00:1A:2B:3C:4D:5E',
                        'storage' => '256 GB',
                        'colour' => 'Space Gray',
                        'marks' => 'Cracked screen',
                    ],
                ],
            ],
        ];

        $resp = $this->actingAs($officer)->postJson("/cases/{$case->id}/associate", $payload);
        $resp->assertStatus(200);
        $resp->assertJson(['success' => true]);

        // Verify Exhibit DB record
        $exhibit = Exhibit::where('multiple_suspect_case_id', $case->id)->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('iPhone 15 Pro Max', $exhibit->dev_model);
        $this->assertEquals('F2LZX984K102', $exhibit->dev_serial_no);
        $this->assertEquals('356938035643809', $exhibit->dev_imei1);
        $this->assertEquals('356938035643817', $exhibit->dev_imei2);
        $this->assertEquals('00:1A:2B:3C:4D:5E', $exhibit->dev_mac_address);
        $this->assertEquals('256 GB', $exhibit->dev_storage);
        $this->assertEquals('Space Gray', $exhibit->dev_colour);
        $this->assertEquals('Cracked screen', $exhibit->dev_marks);

        // Load Digital Forensics show review page
        $showResp = $this->actingAs($officer)->get("/Digital_Forensics/cases/{$case->case_number}/review");
        $showResp->assertStatus(200);
        $html = $showResp->getContent();

        // Check if Exhibit Dossier Section contains the device specifications
        $this->assertStringContainsString('ELECTRONIC DEVICE FORENSIC SPECIFICATIONS', $html);
        $this->assertStringContainsString('iPhone 15 Pro Max', $html);
        $this->assertStringContainsString('F2LZX984K102', $html);
        $this->assertStringContainsString('356938035643809', $html);
        $this->assertStringContainsString('356938035643817', $html);
        $this->assertStringContainsString('00:1A:2B:3C:4D:5E', $html);
        $this->assertStringContainsString('256 GB', $html);
        $this->assertStringContainsString('Space Gray', $html);
        $this->assertStringContainsString('Cracked screen', $html);
    }

    #[Test]
    public function electronic_device_exhibit_saved_in_section4_for_single_suspect_case(): void
    {
        $officer = $this->createOfficer('Digital Forensics', isHead: true);

        $single = \App\Models\SingleSuspectCase::create([
            'case_number' => 'CASE-SING-001',
            'title' => 'Republic v Single Suspect Device Test',
            'unit_name' => 'Digital Forensics',
            'status' => 'Pending Approval',
            'user_id' => $officer->id,
            'suspect_name' => 'Abena Osei',
            'gender' => 'Female',
        ]);

        $payload = [
            'associated_exhibits' => [
                [
                    'associated_person' => 'Abena Osei',
                    'category' => 'Electronic Devices',
                    'seizure_name' => 'Samsung Galaxy S24 Ultra',
                    'commodity_name' => 'Samsung Galaxy S24 Ultra',
                    'dev_model' => 'Samsung Galaxy S24 Ultra',
                    'dev_serial_no' => 'R5CX109988Z',
                    'dev_imei1' => '990000862471854',
                    'dev_imei2' => '990000862471862',
                    'dev_mac_address' => 'AA:BB:CC:DD:EE:FF',
                    'dev_storage' => '512 GB',
                    'dev_colour' => 'Titanium Black',
                    'dev_marks' => 'Scratch on back cover',
                    'dev' => [
                        'model' => 'Samsung Galaxy S24 Ultra',
                        'serial_no' => 'R5CX109988Z',
                        'imei1' => '990000862471854',
                        'imei2' => '990000862471862',
                        'mac_address' => 'AA:BB:CC:DD:EE:FF',
                        'storage' => '512 GB',
                        'colour' => 'Titanium Black',
                        'marks' => 'Scratch on back cover',
                    ],
                ],
            ],
        ];

        $resp = $this->actingAs($officer)->postJson("/cases/{$single->id}/associate", $payload);
        $resp->assertStatus(200);
        $resp->assertJson(['success' => true]);

        // Verify Exhibit DB record
        $exhibit = Exhibit::where('single_suspect_case_id', $single->id)->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('Samsung Galaxy S24 Ultra', $exhibit->dev_model);
        $this->assertEquals('R5CX109988Z', $exhibit->dev_serial_no);

        // Load Digital Forensics show review page
        $showResp = $this->actingAs($officer)->get("/Digital_Forensics/cases/{$single->case_number}/review");
        $showResp->assertStatus(200);
        $html = $showResp->getContent();

        // Check if Exhibit Dossier Section contains the device specifications
        $this->assertStringContainsString('ELECTRONIC DEVICE FORENSIC SPECIFICATIONS', $html);
        $this->assertStringContainsString('Samsung Galaxy S24 Ultra', $html);
        $this->assertStringContainsString('R5CX109988Z', $html);
        $this->assertStringContainsString('990000862471854', $html);
        $this->assertStringContainsString('990000862471862', $html);
        $this->assertStringContainsString('AA:BB:CC:DD:EE:FF', $html);
        $this->assertStringContainsString('512 GB', $html);
        $this->assertStringContainsString('Titanium Black', $html);
        $this->assertStringContainsString('Scratch on back cover', $html);
    }
}
