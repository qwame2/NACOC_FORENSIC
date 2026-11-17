<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeizuresTableMultipleSuspectMugshotsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seizures_commodities_inventory_table_shows_both_suspect_front_mugshots_for_multiple_cases(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-2026-MULTI-MUG',
            'title' => 'Republic vs KWESI MENSAH, YAW BOATENG',
            'unit_name' => 'Crime Scene Support',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'total_suspects_count' => 2,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'KWESI MENSAH',
            'mugshot_front' => 'mugshots/kwesi_front.jpg',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'role' => 'Co-Suspect',
            'suspect_name' => 'YAW BOATENG',
            'mugshot_front' => 'mugshots/yaw_front.jpg',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'case_suspect_id' => $suspect1->id,
            'associated_person' => 'KWESI MENSAH',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Parcel',
        ]);

        $routes = [
            "/cases/{$multiCase->id}/review?from=Crime_Scene_Support",
            "/cases/{$multiCase->id}/review?from=Lab_Analysis",
            "/cases/{$multiCase->id}/review?from=Digital_Forensics",
            "/cases/{$multiCase->id}/review?from=Evidence_Management",
            "/Forensic_Head/cases/{$multiCase->id}/review",
        ];

        foreach ($routes as $route) {
            $resp = $this->followingRedirects()->actingAs($officer)->get($route)->assertOk();
            $html = $resp->getContent();

            // The Seizures table must contain both suspects' front mugshots
            $this->assertStringContainsString('alt="KWESI MENSAH — Front Mugshot"', $html, "Missing KWESI MENSAH front mugshot in [{$route}]");
            $this->assertStringContainsString('alt="YAW BOATENG — Front Mugshot"', $html, "Missing YAW BOATENG front mugshot in [{$route}]");
            $this->assertStringContainsString('data-img-title="Suspect Front Mugshot — KWESI MENSAH"', $html);
            $this->assertStringContainsString('data-img-title="Suspect Front Mugshot — YAW BOATENG"', $html);
        }
    }

    #[Test]
    public function seizures_table_shows_single_front_mugshot_for_single_suspect_case(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-SINGLE-MUG',
            'title' => 'Republic vs AMA SERWAA',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'AMA SERWAA',
            'exhibit_category' => 'Drugs',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
            'mugshot_front' => 'mugshots/ama_front.jpg',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'associated_person' => 'AMA SERWAA',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Heroin',
            'seizure_quantity' => 2,
            'seizure_unit' => 'Blocks',
        ]);

        $resp = $this->followingRedirects()
            ->actingAs($officer)
            ->get("/cases/{$singleCase->case_number}/review?from=Lab_Analysis")
            ->assertOk();

        $html = $resp->getContent();

        $this->assertStringContainsString('alt="AMA SERWAA — Front Mugshot"', $html);
        $this->assertStringContainsString('data-img-title="Suspect Front Mugshot — AMA SERWAA"', $html);
    }
}

