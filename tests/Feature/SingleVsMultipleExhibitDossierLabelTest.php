<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SingleVsMultipleExhibitDossierLabelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function single_suspect_case_renders_primary_exhibit_dossier_label()
    {
        $officer = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => true,
        ]);

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'LAB-2026-SINGLE1',
            'title' => 'State vs Solo Suspect',
            'unit_name' => 'Lab Analysis',
            'suspect_name' => 'Kofi Solo',
            'case_status' => 'Under Investigation',
            'controller_user_id' => $officer->id,
            'docket_data' => [
                'suspect_name' => 'Kofi Solo',
                'seizures' => [
                    [
                        'id' => 'ex-1',
                        'commodity_name' => 'Cocaine Powder',
                        'category' => 'Drugs',
                        'owner_name' => 'Kofi Solo',
                        'seizure_quantity' => '2 parcels',
                    ]
                ]
            ],
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $singleCase->id,
            'name' => 'Cocaine Powder',
            'category' => 'Drugs',
            'quantity' => '2 parcels',
            'associated_person' => 'Kofi Solo',
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$singleCase->case_number}/review");
        $response->assertStatus(200);

        $content = $response->getContent();

        // Must display PRIMARY EXHIBIT DOSSIER and Primary Suspect Seizures & Exhibits exactly once (no duplicates)
        $this->assertStringContainsString('PRIMARY EXHIBIT DOSSIER', $content);
        $this->assertStringContainsString('Primary Suspect Seizures &amp; Exhibits', $content);
        $this->assertSame(1, substr_count($content, 'PRIMARY EXHIBIT DOSSIER'), 'PRIMARY EXHIBIT DOSSIER should appear exactly once');
        $this->assertSame(1, substr_count($content, 'Primary Suspect Seizures'), 'Primary Suspect Seizures should appear exactly once');

        // Must NOT display PRIMARY & CO-SUSPECT
        $this->assertStringNotContainsString('PRIMARY &amp; CO-SUSPECT EXHIBIT DOSSIER', $content);
        $this->assertStringNotContainsString('PRIMARY & CO-SUSPECT EXHIBIT DOSSIER', $content);
        $this->assertStringNotContainsString('Primary &amp; Co-Suspect Seizures &amp; Exhibits', $content);
        $this->assertStringNotContainsString('Primary & Co-Suspect Seizures & Exhibits', $content);
    }

    #[Test]
    public function multiple_suspect_case_renders_primary_and_co_suspect_exhibit_dossier_label()
    {
        $officer = User::factory()->create([
            'unit' => 'Lab Analysis',
            'is_head' => true,
        ]);

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'LAB-2026-MULTI1',
            'title' => 'State vs Duo Suspects',
            'unit_name' => 'Lab Analysis',
            'case_status' => 'Under Investigation',
            'controller_user_id' => $officer->id,
            'docket_data' => [
                'seizures' => [
                    [
                        'id' => 'ex-joint-1',
                        'commodity_name' => 'Heroin Bricks',
                        'category' => 'Drugs',
                        'associated_person' => 'Joint, Both Suspects',
                        'seizure_quantity' => '5 blocks',
                    ]
                ]
            ],
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Kofi One',
            'role' => 'Primary Suspect',
        ]);

        CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Ama Two',
            'role' => 'Co-Suspect',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'name' => 'Heroin Bricks',
            'category' => 'Drugs',
            'quantity' => '5 blocks',
            'associated_person' => 'Joint, Both Suspects',
        ]);

        $response = $this->actingAs($officer)->get("/cases/{$multiCase->case_number}/review");
        $response->assertStatus(200);

        $content = $response->getContent();

        // Must display PRIMARY & CO-SUSPECT
        $hasBadge = str_contains($content, 'PRIMARY &amp; CO-SUSPECT EXHIBIT DOSSIER') || str_contains($content, 'PRIMARY & CO-SUSPECT EXHIBIT DOSSIER');
        $hasHeading = str_contains($content, 'Primary &amp; Co-Suspect Seizures &amp; Exhibits') || str_contains($content, 'Primary & Co-Suspect Seizures & Exhibits');

        $this->assertTrue($hasBadge, 'Multiple suspect case must show PRIMARY & CO-SUSPECT EXHIBIT DOSSIER');
        $this->assertTrue($hasHeading, 'Multiple suspect case must show Primary & Co-Suspect Seizures & Exhibits');
    }
}
