<?php

namespace Tests\Feature;

use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeizureMultiItemExhibitPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function createOfficer(string $unit = 'Crime Scene Support'): User
    {
        return User::factory()->create([
            'fullname' => 'Inspector Kwesi Addo',
            'unit' => $unit,
            'is_head' => true,
            'is_approved' => true,
            'is_staff' => true,
        ]);
    }

    #[Test]
    public function test_all_create_pages_render_contextually_scoped_seizure_photo_upload_elements(): void
    {
        $urls = [
            'Forensics' => '/cases/create',
            'Digital Forensics' => '/Digital_Forensics/create',
            'Evidence Management' => '/Evidence_Management/create',
            'Laboratory Analysis (LAU)' => '/Lab_Analysis/create',
        ];

        foreach ($urls as $unit => $url) {
            $officer = $this->createOfficer($unit);
            $response = $this->actingAs($officer)->get($url);

            $this->assertEquals(200, $response->getStatusCode(), "Failed loading {$url}");
            $html = $response->getContent();

            // Scoped HTML attributes and classes
            $this->assertStringContainsString('triggerSeizurePhotoUpload(this)', $html, "Missing triggerSeizurePhotoUpload(this) in {$url}");
            $this->assertStringContainsString('previewSeizurePhotos(event, this)', $html, "Missing previewSeizurePhotos(event, this) in {$url}");
            $this->assertStringContainsString('handleSeizurePhotoDrop(event, this)', $html, "Missing handleSeizurePhotoDrop(event, this) in {$url}");
            $this->assertStringContainsString('fileSeizurePhoto', $html, "Missing fileSeizurePhoto class in {$url}");
            $this->assertStringContainsString('seizurePhotosGrid', $html, "Missing seizurePhotosGrid class in {$url}");
            $this->assertStringContainsString('seizure-photos-count', $html, "Missing seizure-photos-count class in {$url}");
            $this->assertStringContainsString('seizure-photo-dropzone', $html, "Missing seizure-photo-dropzone class in {$url}");

            // Scoped JS functions
            $this->assertStringContainsString('function getBlockSeizurePhotos', $html, "Missing getBlockSeizurePhotos in {$url}");
            $this->assertStringContainsString('function setBlockSeizurePhotos', $html, "Missing setBlockSeizurePhotos in {$url}");
            $this->assertStringContainsString('function renderSeizureBlockPhotosGrid', $html, "Missing renderSeizureBlockPhotosGrid in {$url}");
            $this->assertStringContainsString('clone._seizurePhotos = []', $html, "Missing clone._seizurePhotos reset in {$url}");
        }
    }

    #[Test]
    public function test_case_creation_stores_scoped_evidence_photos_per_seizure_item(): void
    {
        $officer = $this->createOfficer('Crime Scene Support');

        $photo1 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $photo2 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $payload = [
            'case_type' => 'Single Suspect',
            'title' => 'Republic vs Kwame Mensah',
            'suspect_name' => 'Kwame Mensah',
            'gender' => 'Male',
            'dob' => '1990-05-15',
            'pob' => 'Accra',
            'nationality' => 'Ghanaian',
            'address' => 'House 123, Ring Road, Accra',
            'phone' => '0241234567',
            'offense' => 'Possession of Illicit Substances',
            'date_of_arrest' => '2026-11-10',
            'time_of_arrest' => '14:30',
            'raid_location' => 'Tema Harbour Checkpoint',
            'holding_facility' => 'Tema Regional Cells',
            'has_seizures' => true,
            'seizures' => [
                [
                    'item_number' => 1,
                    'exhibit_category' => 'Drugs',
                    'seizure_name_drug' => 'Cocaine',
                    'seizure_quantity' => '10',
                    'seizure_packaging' => 'Slabs',
                    'seizure_weight' => '10.5',
                    'seizure_unit' => 'Kilograms',
                    'evidence_photos' => [$photo1],
                ],
                [
                    'item_number' => 2,
                    'exhibit_category' => 'Weapons',
                    'seizure_name_weapon' => 'Beretta 92FS Pistol',
                    'seizure_quantity' => '1',
                    'wpn_serial_no' => 'BER-998822',
                    'evidence_photos' => [$photo2],
                ],
            ],
            'evidence_photos' => [$photo1],
        ];

        $response = $this->actingAs($officer)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $caseNumber = $response->json('case.case_number');
        $this->assertNotEmpty($caseNumber);

        $case = ForensicCase::where('case_number', $caseNumber)->first();
        $this->assertNotNull($case);

        $docketData = $case->docket_data;
        $this->assertIsArray($docketData);
        $this->assertArrayHasKey('seizures', $docketData);
        $this->assertCount(2, $docketData['seizures']);

        // Check Item 1 has Photo 1
        $cat1 = $docketData['seizures'][0]['category'] ?? $docketData['seizures'][0]['exhibit_category'];
        $this->assertEquals('Drugs', $cat1);
        $this->assertEquals([$photo1], $docketData['seizures'][0]['evidence_photos']);

        // Check Item 2 has Photo 2 (isolated!)
        $cat2 = $docketData['seizures'][1]['category'] ?? $docketData['seizures'][1]['exhibit_category'];
        $this->assertEquals('Weapons', $cat2);
        $this->assertEquals([$photo2], $docketData['seizures'][1]['evidence_photos']);
    }
}
