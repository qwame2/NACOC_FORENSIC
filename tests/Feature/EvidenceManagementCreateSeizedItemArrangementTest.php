<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceManagementCreateSeizedItemArrangementTest extends TestCase
{
    use RefreshDatabase;

    public function test_evidence_management_head_create_page_renders_well_arranged_seizure_section(): void
    {
        $head = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($head)->get('/Evidence_Management/create');
        $response->assertStatus(200);

        // Check Seizures manifest container
        $response->assertSee('id="seizuresDetailsContainer"', false);
        $response->assertSee('id="seizuresListContainer"', false);
        $response->assertSee('class="seizure-block', false);

        // Check 4-column primary row items
        $response->assertSee('seizure-associated-person-select', false);
        $response->assertSee('id="exhibitSearchInput"', false);
        $response->assertSee('id="commodityFieldWrapper"', false);
        $response->assertSee('id="newSeizureValue"', false);

        // Check Evidence Vault classification row (Exhibit Type & Drug Classification)
        $response->assertSee('exhibit-type-select', false);
        $response->assertSee('drug-classification-select', false);

        // Check Quantification & Packaging Metrics panel
        $response->assertSee('Quantification &amp; Packaging Metrics', false);
        $response->assertSee('id="newSeizureQuantity"', false);
        $response->assertSee('id="newSeizurePackaging"', false);
        $response->assertSee('id="newSeizureWeight"', false);
        $response->assertSee('id="newSeizureUnit"', false);

        // Check all specialized full-width panels
        $response->assertSee('id="deviceFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="vehicleFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="weaponFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="ammoFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="otherExhibitDescriptionWrapper" class="other-exhibit-description-wrapper hidden col-span-full', false);

        // Check Seizure logistics & custody arrangement
        $response->assertSee('id="newSeizureConcealment"', false);
        $response->assertSee('id="newSeizureDate"', false);
        $response->assertSee('id="newSeizureTime"', false);
        $response->assertSee('id="newSeizureLocation"', false);
        $response->assertSee('id="newSeizureInvestigatingOfficer"', false);
        $response->assertSee('id="seizureOfficersContainer"', false);
        $response->assertSee('id="newSeizureSourceRequestingUnit"', false);
        $response->assertSee('id="newSeizureReason"', false);
        $response->assertSee('id="newSeizureRemark"', false);

        // Check Photo dropzone
        $response->assertSee('id="seizurePhotoDropzone"', false);
        $response->assertSee('id="seizurePhotosGrid"', false);

        // Check Add Another Seized Item(s) button is rendered
        $response->assertSee('Add Another Seized Item(s)', false);
    }

    public function test_evidence_management_staff_create_page_renders_well_arranged_seizure_section(): void
    {
        $staff = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($staff)->get('/Evidence_Management/staff/create');
        $response->assertStatus(200);

        // Check Seizures manifest container
        $response->assertSee('id="seizuresDetailsContainer"', false);
        $response->assertSee('id="seizuresListContainer"', false);
        $response->assertSee('class="seizure-block', false);

        // Check 4-column primary row items
        $response->assertSee('seizure-associated-person-select', false);
        $response->assertSee('id="exhibitSearchInput"', false);
        $response->assertSee('id="commodityFieldWrapper"', false);
        $response->assertSee('id="newSeizureValue"', false);

        // Check Evidence Vault classification row (Exhibit Type & Drug Classification)
        $response->assertSee('exhibit-type-select', false);
        $response->assertSee('drug-classification-select', false);

        // Check Quantification & Packaging Metrics panel
        $response->assertSee('Quantification &amp; Packaging Metrics', false);
        $response->assertSee('id="newSeizureQuantity"', false);
        $response->assertSee('id="newSeizurePackaging"', false);
        $response->assertSee('id="newSeizureWeight"', false);
        $response->assertSee('id="newSeizureUnit"', false);

        // Check all specialized full-width panels
        $response->assertSee('id="deviceFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="vehicleFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="weaponFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="ammoFieldsWrapper" class="hidden col-span-full', false);
        $response->assertSee('id="otherExhibitDescriptionWrapper" class="other-exhibit-description-wrapper hidden col-span-full', false);

        // Check Seizure logistics & custody arrangement
        $response->assertSee('id="newSeizureConcealment"', false);
        $response->assertSee('id="newSeizureDate"', false);
        $response->assertSee('id="newSeizureTime"', false);
        $response->assertSee('id="newSeizureLocation"', false);
        $response->assertSee('id="newSeizureInvestigatingOfficer"', false);
        $response->assertSee('id="seizureOfficersContainer"', false);
        $response->assertSee('id="newSeizureSourceRequestingUnit"', false);
        $response->assertSee('id="newSeizureReason"', false);
        $response->assertSee('id="newSeizureRemark"', false);

        // Check Photo dropzone
        $response->assertSee('id="seizurePhotoDropzone"', false);
        $response->assertSee('id="seizurePhotosGrid"', false);

        // Check Add Another Seized Item(s) button is rendered
        $response->assertSee('Add Another Seized Item(s)', false);
    }
}
