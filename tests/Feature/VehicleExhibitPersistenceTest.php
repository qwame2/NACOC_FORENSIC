<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForensicCase;
use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehicleExhibitPersistenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function vehicle_and_weapon_attributes_are_persisted_in_exhibits_table_on_case_creation()
    {
        $user = User::factory()->create([
            'unit' => 'Crime Scene Support',
        ]);

        $seizures = [
            [
                'exhibit_number' => '1',
                'category' => 'Vehicle / Vessel',
                'seizure_name' => 'Toyota Land Cruiser',
                'veh_make' => 'Toyota',
                'veh_model' => 'Land Cruiser',
                'veh_type' => 'SUV',
                'veh_year' => '2023',
                'veh_colour' => 'Midnight Black',
                'veh_reg_no' => 'GR-9821-23',
                'veh_vin' => 'JT111222333444555',
                'veh_engine_no' => 'ENG-99887766',
                'veh_country_reg' => 'Ghana',
                'veh_owner' => 'John Doe',
                'veh_owner_contact' => '+233 24 555 6789',
                'veh_driver' => 'John Doe',
                'veh_docs_available' => 'Yes',
                'veh_doc_ref' => 'DVLA-LOG-2023-8899',
                'veh_condition' => 'Good Condition / Running',
                'veh_fuel' => 'Full Tank',
                'veh_keys_recv' => 'Yes',
                'veh_key_count' => '2 Keys with Remote',
                'veh_damage' => 'Minor dent on rear left fender',
                'veh_mods' => 'Concealed storage box beneath trunk floorboard',
                'veh_features' => 'Tinted windows, GPS tracking unit under dash',
                'evidence_photos' => ['/storage/evidence/photos/veh_sample1.jpg', '/storage/evidence/photos/veh_sample2.jpg'],
            ],
            [
                'exhibit_number' => '2',
                'category' => 'Weapon / Firearm',
                'seizure_name' => 'Glock 19',
                'wpn_model' => 'Glock 19',
                'wpn_serial_no' => 'WP-778899',
                'wpn_calibre' => '9mm',
                'wpn_colour' => 'Black',
                'wpn_size' => 'Medium',
                'wpn_marks' => 'Engraved initials JD on frame',
                'wpn_location_found' => 'Under driver front seat',
                'wpn_loaded_status' => 'Loaded (15 rounds in magazine)',
                'wpn_condition' => 'Operational / Clean',
                'evidence_photos' => ['/storage/evidence/photos/wpn_sample1.jpg'],
            ]
        ];

        $payload = [
            'unit_name' => 'Crime Scene Support',
            'title' => 'Republic vs John Doe & Jane Doe',
            'suspect_name' => 'John Doe',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'John Doe', 'role' => 'Primary Suspect'],
                    ['suspect_name' => 'Jane Doe', 'role' => 'Associate Suspect'],
                ],
                'seizures' => $seizures,
            ],
            'user_id' => $user->id,
        ];

        $case = ForensicCase::create($payload);

        $this->assertNotNull($case);

        $vehExhibit = Exhibit::where('exhibit_category', 'Vehicle / Vessel')->first();

        $this->assertNotNull($vehExhibit);
        $this->assertEquals('Toyota', $vehExhibit->veh_make);
        $this->assertEquals('Land Cruiser', $vehExhibit->veh_model);
        $this->assertEquals('SUV', $vehExhibit->veh_type);
        $this->assertEquals('2023', $vehExhibit->veh_year);
        $this->assertEquals('Midnight Black', $vehExhibit->veh_colour);
        $this->assertEquals('GR-9821-23', $vehExhibit->veh_reg_no);
        $this->assertEquals('JT111222333444555', $vehExhibit->veh_vin);
        $this->assertEquals('ENG-99887766', $vehExhibit->veh_engine_no);
        $this->assertEquals('Ghana', $vehExhibit->veh_country_reg);
        $this->assertEquals('John Doe', $vehExhibit->veh_owner);
        $this->assertEquals('+233 24 555 6789', $vehExhibit->veh_owner_contact);
        $this->assertEquals('John Doe', $vehExhibit->veh_driver);
        $this->assertEquals('Yes', $vehExhibit->veh_docs_available);
        $this->assertEquals('DVLA-LOG-2023-8899', $vehExhibit->veh_doc_ref);
        $this->assertEquals('Good Condition / Running', $vehExhibit->veh_condition);
        $this->assertEquals('Full Tank', $vehExhibit->veh_fuel);
        $this->assertEquals('Yes', $vehExhibit->veh_keys_recv);
        $this->assertEquals('2 Keys with Remote', $vehExhibit->veh_key_count);
        $this->assertEquals('Minor dent on rear left fender', $vehExhibit->veh_damage);
        $this->assertEquals('Concealed storage box beneath trunk floorboard', $vehExhibit->veh_mods);
        $this->assertEquals('Tinted windows, GPS tracking unit under dash', $vehExhibit->veh_features);
        $this->assertContains('/storage/evidence/photos/veh_sample1.jpg', $vehExhibit->evidence_photos);

        $wpnExhibit = Exhibit::where('exhibit_category', 'Weapon / Firearm')->first();

        $this->assertNotNull($wpnExhibit);
        $this->assertEquals('Glock 19', $wpnExhibit->wpn_model);
        $this->assertEquals('WP-778899', $wpnExhibit->wpn_serial_no);
        $this->assertEquals('9mm', $wpnExhibit->wpn_calibre);
        $this->assertEquals('Engraved initials JD on frame', $wpnExhibit->wpn_marks);
        $this->assertEquals('Under driver front seat', $wpnExhibit->wpn_location_found);
        $this->assertEquals('Loaded (15 rounds in magazine)', $wpnExhibit->wpn_loaded_status);
        $this->assertEquals('Operational / Clean', $wpnExhibit->wpn_condition);
        $this->assertContains('/storage/evidence/photos/wpn_sample1.jpg', $wpnExhibit->evidence_photos);

        // Also verify review view contains these rendered fields
        $user->is_approved = true;
        $user->save();
        $response = $this->actingAs($user)->get("/cases/{$case->case_number}/review");
        $response->assertStatus(200);
        $response->assertSee('DVLA-LOG-2023-8899');
        $response->assertSee('+233 24 555 6789');
        $response->assertSee('Concealed storage box beneath trunk floorboard');
        $response->assertSee('Engraved initials JD on frame');
        $response->assertSee('Under driver front seat');
        $response->assertSee('/storage/evidence/photos/veh_sample1.jpg');
    }

    #[Test]
    public function other_exhibit_description_is_persisted_in_exhibits_table()
    {
        $user = User::factory()->create([
            'unit' => 'Evidence Management',
        ]);

        $seizures = [
            [
                'exhibit_number' => '1',
                'category' => 'Other Exhibits',
                'seizure_name' => 'Custom Crypto Mining Rig',
                'other_exhibit_description' => '12x RTX 4090 GPU custom server rack used for illicit crypto operations.',
            ]
        ];

        $payload = [
            'unit_name' => 'Evidence Management',
            'title' => 'Republic vs Cyber Suspect',
            'suspect_name' => 'Cyber Suspect',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Cyber Suspect', 'role' => 'Primary Suspect'],
                ],
                'seizures' => $seizures,
            ],
            'user_id' => $user->id,
        ];

        $case = ForensicCase::create($payload);

        $otherExhibit = Exhibit::where('exhibit_category', 'Other Exhibits')->first();

        $this->assertNotNull($otherExhibit);
        $this->assertEquals('12x RTX 4090 GPU custom server rack used for illicit crypto operations.', $otherExhibit->other_exhibit_description);
    }

    #[Test]
    public function device_storage_colour_and_marks_are_persisted_in_exhibits_table()
    {
        $user = User::factory()->create([
            'unit' => 'Digital Forensics',
        ]);

        $seizures = [
            [
                'exhibit_number' => '1',
                'category' => 'Electronic Devices',
                'seizure_name' => 'iPhone 15 Pro Max',
                'dev_model' => 'iPhone 15 Pro Max',
                'dev_serial_no' => 'IPH-99881122',
                'dev_imei1' => '359876543210123',
                'dev_imei2' => '359876543210456',
                'dev_mac_address' => '00:1A:2B:3C:4D:5E',
                'dev_storage' => '512 GB NVMe',
                'dev_colour' => 'Natural Titanium',
                'dev_marks' => 'Slight scratch near camera ring',
            ]
        ];

        $payload = [
            'unit_name' => 'Digital Forensics',
            'title' => 'Republic vs Kwame Mensah',
            'suspect_name' => 'Kwame Mensah',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Kwame Mensah', 'role' => 'Primary Suspect'],
                ],
                'seizures' => $seizures,
            ],
            'user_id' => $user->id,
        ];

        $case = ForensicCase::create($payload);

        $this->assertNotNull($case);

        $devExhibit = Exhibit::where('exhibit_category', 'Electronic Devices')->first();
        $this->assertNotNull($devExhibit);
        $this->assertEquals('iPhone 15 Pro Max', $devExhibit->dev_model);
        $this->assertEquals('512 GB NVMe', $devExhibit->dev_storage);
        $this->assertEquals('Natural Titanium', $devExhibit->dev_colour);
        $this->assertEquals('Slight scratch near camera ring', $devExhibit->dev_marks);
    }

    #[Test]
    public function multiple_suspect_phones_and_device_specs_render_in_review_page()
    {
        $user = User::factory()->create([
            'unit' => 'Digital Forensics',
            'is_approved' => true,
        ]);

        $seizures = [
            [
                'exhibit_number' => '1',
                'category' => 'Electronic Devices',
                'seizure_name' => 'Samsung Galaxy S24 Ultra',
                'dev_model' => 'Samsung Galaxy S24 Ultra',
                'dev_serial_no' => 'SM-S928B-001',
                'dev_imei1' => '351234567890123',
                'dev_imei2' => '351234567890456',
                'dev_mac_address' => 'AA:BB:CC:DD:EE:FF',
                'dev_storage' => '1 TB UFS 4.0',
                'dev_colour' => 'Titanium Gray',
                'dev_marks' => 'Cracked screen protector on top right',
            ]
        ];

        $phones = [
            ['label' => 'Primary', 'number' => '+233 24 111 2222'],
            ['label' => 'WhatsApp', 'number' => '+233 20 333 4444'],
            ['label' => 'Secondary', 'number' => '+233 55 777 8888'],
        ];

        $payload = [
            'unit_name' => 'Digital Forensics',
            'title' => 'Republic vs John Techie',
            'suspect_name' => 'John Techie',
            'phone' => '+233 24 111 2222',
            'phones' => $phones,
            'docket_data' => [
                'phone' => '+233 24 111 2222',
                'phones' => $phones,
                'suspects' => [
                    [
                        'suspect_name' => 'John Techie',
                        'role' => 'Primary Suspect',
                        'phone' => '+233 24 111 2222',
                        'phones' => $phones,
                    ],
                ],
                'seizures' => $seizures,
            ],
            'user_id' => $user->id,
        ];

        $case = ForensicCase::create($payload);

        $response = $this->actingAs($user)->get("/cases/{$case->case_number}/review");

        $response->assertStatus(200);

        // Assert all multiple phone numbers and labels are rendered
        $response->assertSee('+233 24 111 2222');
        $response->assertSee('+233 20 333 4444');
        $response->assertSee('+233 55 777 8888');
        $response->assertSee('WhatsApp');
        $response->assertSee('Secondary');

        // Assert device specifications are rendered in review
        $response->assertSee('1 TB UFS 4.0');
        $response->assertSee('Titanium Gray');
        $response->assertSee('Cracked screen protector on top right');
    }

    #[Test]
    public function exhibit_remark_is_persisted_in_exhibits_table()
    {
        $user = User::factory()->create([
            'unit' => 'Digital Forensics',
        ]);

        $seizures = [
            [
                'exhibit_number' => '1',
                'category' => 'Electronic Devices',
                'seizure_name' => 'MacBook Pro 16',
                'dev_model' => 'A2485',
                'exhibit_remark' => 'Packaging seal unbroken; chain of custody tag #4459 attached.',
            ]
        ];

        $payload = [
            'unit_name' => 'Digital Forensics',
            'title' => 'Republic vs Kofi Tech',
            'suspect_name' => 'Kofi Tech',
            'exhibit_remark' => 'Packaging seal unbroken; chain of custody tag #4459 attached.',
            'docket_data' => [
                'suspects' => [
                    ['suspect_name' => 'Kofi Tech', 'role' => 'Primary Suspect'],
                ],
                'seizures' => $seizures,
            ],
            'user_id' => $user->id,
        ];

        $case = ForensicCase::create($payload);

        $this->assertNotNull($case);

        $exhibit = Exhibit::where('commodity_name', 'MacBook Pro 16')->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('Packaging seal unbroken; chain of custody tag #4459 attached.', $exhibit->exhibit_remark);
    }
}

