<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\SingleSuspectCase;
use App\Models\SuspectGsaReport;
use App\Models\SuspectPreliminaryReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreliminaryReportCardSuspectNameTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function preliminary_report_card_omits_suspect_name_pill_while_preserving_db_link_and_gsa_owner_pill(): void
    {
        $officer = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_approved' => true,
            'is_head' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'NCC-2026-3483',
            'laboratory_number' => 'NLA-FM-T3483-F',
            'title' => 'Republic vs LINDA BENS',
            'unit_name' => 'Crime Scene Support',
            'suspect_name' => 'LINDA BENS',
            'exhibit_category' => 'Drugs',
            'status' => 'Approved',
            'case_status' => 'Under Investigation',
            'priority' => 'HIGH',
            'user_id' => $officer->id,
        ]);

        $suspect = CaseSuspect::create([
            'single_suspect_case_id' => $case->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'LINDA BENS',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'associated_person' => 'LINDA BENS',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Cocaine',
            'seizure_quantity' => 1,
            'seizure_unit' => 'Parcel',
        ]);

        $prelim = SuspectPreliminaryReport::create([
            'single_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'case_number' => 'NCC-2026-3483',
            'original_filename' => 'Preliminary_Report_NLA-FM-T3483-F.pdf',
            'stored_filename' => 'Preliminary_Report_NLA-FM-T3483-F.pdf',
            'file_path' => '/Lab_Analysis/preliminary-report-pdf/NCC-2026-3483',
            'file_extension' => 'pdf',
            'file_size' => 0,
            'status' => 'active',
        ]);
        $prelim->forceFill(['created_at' => '2026-11-14 07:15:00'])->saveQuietly();
        $prelim->load('suspect');

        $gsa = SuspectGsaReport::create([
            'single_suspect_case_id' => $case->id,
            'case_suspect_id' => $suspect->id,
            'case_number' => 'NCC-2026-3483',
            'original_filename' => 'GSA_Certificate_T3483.pdf',
            'stored_filename' => 'gsa_t3483.pdf',
            'file_path' => 'gsa_reports/gsa_t3483.pdf',
            'file_extension' => 'pdf',
            'file_size' => 2048,
            'status' => 'active',
        ]);
        $gsa->load('suspect');

        // 1. Direct partial assertion: Preliminary card has no suspect name pill; GSA card retains its owner pill.
        $partialHtml = view('partials.gsa_report_files', [
            'gsaFiles' => collect([$prelim, $gsa]),
            'gsaForName' => null,
        ])->render();

        $this->assertStringContainsString('Preliminary_Report_NLA-FM-T3483-F.pdf', $partialHtml);
        $this->assertStringContainsString('System Generated', $partialHtml);
        $this->assertStringContainsString('Nov 14, 2026 07:15', $partialHtml);
        $this->assertSame(1, substr_count($partialHtml, 'title="Associated: LINDA BENS"'));

        $prelimOnlyHtml = view('partials.gsa_report_files', [
            'gsaFiles' => collect([$prelim]),
            'gsaForName' => null,
        ])->render();

        $this->assertStringContainsString('Preliminary_Report_NLA-FM-T3483-F.pdf', $prelimOnlyHtml);
        $this->assertStringContainsString('System Generated', $prelimOnlyHtml);
        $this->assertStringContainsString('Nov 14, 2026 07:15', $prelimOnlyHtml);
        $this->assertStringNotContainsString('LINDA BENS', $prelimOnlyHtml);

        // 2. Database relationship remains safely preserved.
        $this->assertSame('LINDA BENS', $prelim->fresh('suspect')->suspect?->suspect_name);

        // 3. Full review page rendering still passes and omits suspect pill on Preliminary_Report card.
        $response = $this->followingRedirects()
            ->actingAs($officer)
            ->get("/cases/{$case->id}/review?from=Crime_Scene_Support")
            ->assertOk();

        $reviewHtml = $response->getContent();
        $this->assertStringContainsString('Preliminary_Report_NLA-FM-T3483-F.pdf', $reviewHtml);
        $this->assertSame(1, substr_count($reviewHtml, 'title="Associated: LINDA BENS"'));
    }
}

