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
                'veh_driver' => 'John Doe',
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
        $this->assertEquals('John Doe', $vehExhibit->veh_driver);

        $wpnExhibit = Exhibit::where('exhibit_category', 'Weapon / Firearm')->first();

        $this->assertNotNull($wpnExhibit);
        $this->assertEquals('Glock 19', $wpnExhibit->wpn_model);
        $this->assertEquals('WP-778899', $wpnExhibit->wpn_serial_no);
        $this->assertEquals('9mm', $wpnExhibit->wpn_calibre);
    }
}
