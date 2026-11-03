<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SingleSuspectCase;
use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaseCreatorAndStatusUpdaterDisplayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function review_page_displays_case_creator_and_status_updater_and_hides_clearance_box()
    {
        $creatorUser = User::factory()->create([
            'fullname' => 'Sergeant Kwame Creator',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        $updaterUser = User::factory()->create([
            'fullname' => 'Inspector John Updater',
            'unit' => 'Lab Analysis',
            'is_head' => true,
        ]);

        $case = SingleSuspectCase::create([
            'case_number' => 'CSS-2026-STATUS-DISP',
            'title' => 'Republic vs Kofi Mensah',
            'unit_name' => 'Crime Scene Support',
            'arresting_officer' => 'Constable Arresting Officer',
            'user_id' => $creatorUser->id,
            'suspect_name' => 'Kofi Mensah',
            'case_status' => 'Under Investigation',
        ]);

        Exhibit::create([
            'single_suspect_case_id' => $case->id,
            'name' => 'Seized Weapon',
            'category' => 'Weapons',
            'quantity' => '1',
            'associated_person' => 'Kofi Mensah',
        ]);

        // Inspector John Updater updates the case status to "On Remand"
        $updateResp = $this->actingAs($updaterUser)
            ->postJson("/cases/{$case->case_number}/case-status", [
                'case_status' => 'On Remand',
            ]);
        $updateResp->assertStatus(200);

        // Load the review page
        $reviewResp = $this->actingAs($updaterUser)->get("/cases/{$case->case_number}/review");
        $reviewResp->assertStatus(200);

        $content = $reviewResp->getContent();

        // 1. Verify Case Created By displays Sergeant Kwame Creator (the creator user, not arresting officer)
        $this->assertStringContainsString('Created By:', $content);
        $this->assertStringContainsString('Sergeant Kwame Creator', $content);

        // 2. Verify Arresting Officer displays Constable Arresting Officer
        $this->assertStringContainsString('Arresting Officer:', $content);
        $this->assertStringContainsString('Constable Arresting Officer', $content);

        // 3. Verify Status Updated By displays Inspector John Updater
        $this->assertStringContainsString('Status Updated By:', $content);
        $this->assertStringContainsString('Inspector John Updater', $content);

        // 4. Verify the misleading clearance box is NOT shown
        $this->assertStringNotContainsString('Official Clearance Justification / Remark:', $content);
    }

    #[Test]
    public function multiple_suspect_case_review_page_displays_creator_and_arresting_officer()
    {
        $creatorUser = User::factory()->create([
            'fullname' => 'Captain Mensah Field',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
        ]);

        $multiCase = \App\Models\MultipleSuspectCase::create([
            'case_number' => 'CSS-2026-MULTI-OFFICER',
            'title' => 'Republic vs Multi Gang',
            'unit_name' => 'Crime Scene Support',
            'arresting_officer' => 'Officer Daniel Arresting',
            'user_id' => $creatorUser->id,
            'total_suspects_count' => 2,
            'case_status' => 'Under Investigation',
        ]);

        \App\Models\CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Primary Multi Suspect',
            'role' => 'Primary Suspect',
        ]);

        Exhibit::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'name' => 'Primary Multi Seizure',
            'category' => 'Weapons',
            'quantity' => '1',
            'associated_person' => 'Primary Multi Suspect',
        ]);

        $reviewResp = $this->actingAs($creatorUser)->get("/cases/{$multiCase->case_number}/review");
        $reviewResp->assertStatus(200);

        $content = $reviewResp->getContent();

        $this->assertStringContainsString('Created By:', $content);
        $this->assertStringContainsString('Captain Mensah Field', $content);
        $this->assertStringContainsString('Arresting Officer:', $content);
        $this->assertStringContainsString('Officer Daniel Arresting', $content);
    }
}
