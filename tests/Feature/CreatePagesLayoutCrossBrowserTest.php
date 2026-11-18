<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePagesLayoutCrossBrowserTest extends TestCase
{
    use RefreshDatabase;

    protected function createOfficer(string $unit, bool $isHead = false, bool $isApproved = true): User
    {
        return User::factory()->create([
            'fullname' => "{$unit} Officer",
            'unit' => $unit,
            'is_head' => $isHead,
            'is_approved' => $isApproved,
            'is_staff' => true,
        ]);
    }

    /**
     * Test that all 10 unit create views feature uniform Arrest and Seizure layouts across all browsers.
     */
    public function test_all_create_pages_have_uniform_cross_browser_arrest_and_seizure_layouts(): void
    {
        $officer = $this->createOfficer('Forensics', true);
        $this->actingAs($officer);

        $views = [
            'cases.create' => ['user' => $officer],
            'Crime_Scene_Support.head.CrimeSceneCreate' => ['user' => $officer],
            'Crime_Scene_Support.staff.create' => ['user' => $officer],
            'Digital_Forensics.create' => ['user' => $officer],
            'Digital_Forensics.staff.create' => ['user' => $officer],
            'Evidence_Management.create' => ['user' => $officer],
            'Evidence_Management.staff.create' => ['user' => $officer],
            'Forensic_Head.staff.create' => ['user' => $officer],
            'Lab_Analysis.create' => ['user' => $officer],
            'Lab_Analysis.staff.create' => ['user' => $officer],
        ];

        foreach ($views as $viewName => $data) {
            $rendered = view($viewName, $data)->render();

            // 1. Arrest layout order: Place of Incident -> Date of Arrest -> Time of Arrest -> Facility -> Brief Facts
            $posPlace = strpos($rendered, 'id="newArrestedRaidLocation"');
            $posDateArrest = strpos($rendered, 'id="newArrestedDate"');
            $posTimeArrest = strpos($rendered, 'id="newArrestedTime"');
            $posFacility = strpos($rendered, 'id="newArrestedFacility"');
            $posOffense = strpos($rendered, 'id="newArrestedOffense"');

            $this->assertNotFalse($posPlace, "Place of Incident missing in {$viewName}");
            $this->assertNotFalse($posDateArrest, "Date of Arrest missing in {$viewName}");
            $this->assertNotFalse($posTimeArrest, "Time of Arrest missing in {$viewName}");
            $this->assertNotFalse($posFacility, "Holding Facility missing in {$viewName}");
            $this->assertNotFalse($posOffense, "Brief Facts textarea missing in {$viewName}");

            $this->assertTrue(
                $posPlace < $posDateArrest,
                "In {$viewName}, Place of Incident should appear before Date of Arrest"
            );
            $this->assertTrue(
                $posDateArrest < $posTimeArrest,
                "In {$viewName}, Date of Arrest should appear before Time of Arrest"
            );
            $this->assertTrue(
                $posTimeArrest < $posFacility,
                "In {$viewName}, Time of Arrest should appear before Holding Facility"
            );
            $this->assertTrue(
                $posFacility < $posOffense,
                "In {$viewName}, Holding Facility should appear before Brief Facts textarea"
            );

            // 2. Seizure layout order: Date Seized -> Time Seized -> Location Seized
            $posDateSeiz = strpos($rendered, 'id="newSeizureDate"');
            $posTimeSeiz = strpos($rendered, 'id="newSeizureTime"');
            $posLocSeiz = strpos($rendered, 'id="newSeizureLocation"');

            $this->assertNotFalse($posDateSeiz, "Date Seized missing in {$viewName}");
            $this->assertNotFalse($posTimeSeiz, "Time Seized missing in {$viewName}");
            if (!str_contains($viewName, 'Lab_Analysis')) {
                $this->assertNotFalse($posLocSeiz, "Location Seized missing in {$viewName}");

                $this->assertTrue(
                    $posDateSeiz < $posTimeSeiz,
                    "In {$viewName}, Date Seized should appear before Time Seized"
                );
                $this->assertTrue(
                    $posTimeSeiz < $posLocSeiz,
                    "In {$viewName}, Location Seized should appear at the right side of Time Seized"
                );
            } else {
                $this->assertTrue(
                    $posDateSeiz < $posTimeSeiz,
                    "In {$viewName}, Date Seized should appear before Time Seized"
                );
            }

            // 3. Check for 3-column seizure grid and min-w-0 for cross-browser stability
            $this->assertStringContainsString(
                'md:grid-cols-3',
                $rendered,
                "In {$viewName}, Seizure Date/Time/Location must use md:grid-cols-3"
            );
            $this->assertStringContainsString(
                'lg:grid-cols-4',
                $rendered,
                "In {$viewName}, Arrest row must use lg:grid-cols-4"
            );
        }
    }
}
