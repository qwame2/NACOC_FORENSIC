<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalForensicsCreateSeizedItemArrangementTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_forensics_head_create_page_renders_well_arranged_seizure_section(): void
    {
        $head = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($head)->get('/Digital_Forensics/create');
        $response->assertStatus(200);

        // Check Seizures manifest container
        $response->assertSee('id="seizuresDetailsContainer"', false);
        $response->assertSee('id="seizuresListContainer"', false);
        $response->assertSee('class="seizure-block', false);

        // Check 4-column primary row items
        $response->assertSee('seizure-associated-person-select', false);
        $response->assertSee('id="exhibitSearchInput"', false);
        $response->assertSee('id="commodityFieldWrapper"', false);
        $response->assertSee('id="newSeizureQuantity"', false);

        // Check Row 2 items (Estimated Street Value & Concealment)
        $response->assertSee('id="newSeizureValue"', false);
        $response->assertSee('id="newSeizureConcealment"', false);

        // Check all specialized full-width panels
        $response->assertSee('id="deviceFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="vehicleFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="weaponFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="ammoFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="otherExhibitDescriptionWrapper" class="other-exhibit-description-wrapper hidden col-span-full', false);

        // Check Seizure logistics & custody arrangement
        $response->assertSee('id="newSeizureDate"', false);
        $response->assertSee('id="newSeizureTime"', false);
        $response->assertSee('id="newSeizureLocation"', false);
        $response->assertSee('id="seizureOfficersContainer"', false);
        $response->assertSee('id="newSeizureReason"', false);
        $response->assertSee('id="newSeizureRemark"', false);

        // Check Photo dropzone
        $response->assertSee('id="seizurePhotoDropzone"', false);
        $response->assertSee('id="seizurePhotosGrid"', false);

        // Dynamic seizure manifest controls
        $response->assertSee('id="seizureCountInput"', false);
    }

    public function test_digital_forensics_staff_create_page_renders_well_arranged_seizure_section(): void
    {
        $staff = User::factory()->create([
            'unit' => 'Digital & Physical Forensics',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($staff)->get('/Digital_Forensics/staff/create');
        $response->assertStatus(200);

        // Check Seizures manifest container
        $response->assertSee('id="seizuresDetailsContainer"', false);
        $response->assertSee('id="seizuresListContainer"', false);
        $response->assertSee('class="seizure-block', false);

        // Check 4-column primary row items
        $response->assertSee('seizure-associated-person-select', false);
        $response->assertSee('id="exhibitSearchInput"', false);
        $response->assertSee('id="commodityFieldWrapper"', false);
        $response->assertSee('id="newSeizureQuantity"', false);

        // Check Row 2 items (Estimated Street Value & Concealment)
        $response->assertSee('id="newSeizureValue"', false);
        $response->assertSee('id="newSeizureConcealment"', false);

        // Check all specialized full-width panels
        $response->assertSee('id="deviceFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="vehicleFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="weaponFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="ammoFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="otherExhibitDescriptionWrapper" class="other-exhibit-description-wrapper hidden col-span-full', false);

        // Check Seizure logistics & custody arrangement
        $response->assertSee('id="newSeizureDate"', false);
        $response->assertSee('id="newSeizureTime"', false);
        $response->assertSee('id="newSeizureLocation"', false);
        $response->assertSee('id="seizureOfficersContainer"', false);
        $response->assertSee('id="newSeizureReason"', false);
        $response->assertSee('id="newSeizureRemark"', false);

        // Check Photo dropzone
        $response->assertSee('id="seizurePhotoDropzone"', false);
        $response->assertSee('id="seizurePhotosGrid"', false);

        // Dynamic seizure manifest controls
        $response->assertSee('id="seizureCountInput"', false);
    }
}

