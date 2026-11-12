<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonnelRosterPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $headUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headUser = User::factory()->create([
            'fullname' => 'Director Ama Mensah',
            'username' => 'ama.mensah',
            'service_id' => 'NAC-DIR-001',
            'phone_number' => '0244000001',
            'unit' => 'Forensic Directorate',
            'is_head' => true,
            'is_approved' => true,
            'email' => 'ama.mensah@nacoc.gov.gh',
        ]);
    }

    #[Test]
    public function non_head_officers_are_blocked_from_personnel_roster(): void
    {
        $staff = User::factory()->create([
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $this->actingAs($staff)
            ->get('/Forensic_Head/personnel-roster')
            ->assertRedirect('/cases');
    }

    #[Test]
    public function personnel_roster_displays_all_system_users_and_sidebar_link(): void
    {
        $cssOfficer = User::factory()->create([
            'fullname' => 'Insp. Kofi Boateng',
            'service_id' => 'NAC-CSS-010',
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $labPending = User::factory()->create([
            'fullname' => 'Chemist Yaa Asantewaa',
            'service_id' => 'NAC-LAB-022',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_approved' => false,
        ]);

        $dpfHead = User::factory()->create([
            'fullname' => 'Supt. Kwaku Frimpong',
            'service_id' => 'NAC-DPF-002',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => true,
            'is_approved' => true,
        ]);

        $resp = $this->actingAs($this->headUser)->get('/Forensic_Head/personnel-roster');
        $resp->assertStatus(200);

        // Displays all users including head, approved officers, and pending accounts
        $resp->assertSee('Director Ama Mensah');
        $resp->assertSee('Insp. Kofi Boateng');
        $resp->assertSee('Chemist Yaa Asantewaa');
        $resp->assertSee('Supt. Kwaku Frimpong');

        // Sidebar Personnel Details link targets the page without staff count suffix
        $resp->assertSee('/Forensic_Head/personnel-roster', false);
        $resp->assertSee('Personnel Details');
        $resp->assertDontSee('Personnel Roster');
        $resp->assertDontSee('sidebarTotalPersonnelCount', false);

        // 10s Sync badge and Pending Verification KPI cardview are excluded
        $resp->assertDontSee('10s Sync');
        $resp->assertDontSee('id="kpiPendingCount"', false);
        $resp->assertDontSee('Awaiting Clearance');
        $resp->assertSee('LAU');
        $resp->assertDontSee('CFLU');

        // Skeleton loader, AJAX engine, debounce input, and interval polling hooks present
        $resp->assertSee('initAjaxTable', false);
        $resp->assertSee('renderPersonnelSkeletonRows', false);
        $resp->assertSee('setInterval(pollPersonnelRoster', false);
        $resp->assertSee('data-table-filter="#personnelTableContainer"', false);

        // Updated Forensic Department / Department Head terminology
        $resp->assertSee('Forensic Department Personnel Details');
        $resp->assertSee('All Registered System Personnel Across Department & Operational Units');
        $resp->assertSee('Forensic Department (FDept)');
        $resp->assertSee('FDept');
        $resp->assertSee('Forensic Department');
        $resp->assertSee('Department Head');
        $resp->assertSee('Units / Department Heads');

        $resp->assertDontSee('FDIR');
        $resp->assertDontSee('Forensic Directorate Personnel Roster');
        $resp->assertDontSee('All Registered System Personnel Across Directorate & Operational Units');
        $resp->assertDontSee('Unit / Directorate Heads');
        $resp->assertDontSee('Directorate Head');
    }

    #[Test]
    public function personnel_roster_filters_by_unit_role_status_and_search_via_ajax(): void
    {
        User::factory()->create([
            'fullname' => 'Officer Samuel Osei',
            'username' => 'samuel.osei',
            'service_id' => 'NAC-EMU-031',
            'phone_number' => '0551234567',
            'unit' => 'Evidence Management',
            'is_head' => false,
            'is_approved' => true,
            'email' => 'samuel.osei@nacoc.gov.gh',
        ]);

        User::factory()->create([
            'fullname' => 'Trainee Efua Adjei',
            'username' => 'efua.adjei',
            'service_id' => 'NAC-LAB-099',
            'phone_number' => '0209998887',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_approved' => false,
            'email' => 'efua.adjei@nacoc.gov.gh',
        ]);

        // Filter by unit = em via AJAX
        $unitResp = $this->actingAs($this->headUser)->get('/Forensic_Head/personnel-roster?unit=em', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $unitResp->assertStatus(200);
        $unitResp->assertSee('id="personnelTable"', false);
        $unitResp->assertSee('Officer Samuel Osei');
        $unitResp->assertDontSee('Trainee Efua Adjei');
        $unitResp->assertDontSee('Director Ama Mensah');

        // Filter by role = head via AJAX
        $roleResp = $this->actingAs($this->headUser)->get('/Forensic_Head/personnel-roster?role=head', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $roleResp->assertStatus(200);
        $roleResp->assertSee('Director Ama Mensah');
        $roleResp->assertDontSee('Officer Samuel Osei');

        // Filter by status = pending via AJAX
        $statusResp = $this->actingAs($this->headUser)->get('/Forensic_Head/personnel-roster?status=pending', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $statusResp->assertStatus(200);
        $statusResp->assertSee('Trainee Efua Adjei');
        $statusResp->assertDontSee('Officer Samuel Osei');

        // Search by service_id via AJAX
        $searchResp = $this->actingAs($this->headUser)->get('/Forensic_Head/personnel-roster?search=NAC-EMU-031', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $searchResp->assertStatus(200);
        $searchResp->assertSee('Officer Samuel Osei');
        $searchResp->assertDontSee('Trainee Efua Adjei');
    }

    #[Test]
    public function personnel_roster_polling_endpoint_returns_json_with_html_and_live_stats(): void
    {
        User::factory()->create([
            'fullname' => 'Pending Chemist Kojo',
            'unit' => 'Lab Analysis',
            'is_head' => false,
            'is_approved' => false,
        ]);

        $pollResp = $this->actingAs($this->headUser)->get('/Forensic_Head/personnel-roster?poll=1', [
            'Accept' => 'application/json',
        ]);

        $pollResp->assertStatus(200);
        $pollResp->assertJsonStructure([
            'html',
            'stats' => ['total', 'heads', 'approved', 'pending', 'units'],
            'pagination' => ['first_item', 'last_item', 'total'],
        ]);
        $pollResp->assertJsonPath('stats.total', 2);
        $pollResp->assertJsonPath('stats.heads', 1);
        $pollResp->assertJsonPath('stats.approved', 1);
        $pollResp->assertJsonPath('stats.pending', 1);
        $this->assertStringContainsString('Pending Chemist Kojo', $pollResp->json('html'));
    }

    #[Test]
    public function personnel_roster_supports_pagination(): void
    {
        User::factory()->count(10)->create([
            'unit' => 'Crime Scene Support',
            'is_head' => false,
            'is_approved' => true,
        ]);

        $page1 = $this->actingAs($this->headUser)->get('/Forensic_Head/personnel-roster?per_page=5&page=1');
        $page1->assertStatus(200);
        $page1->assertSee('Showing');

        $page2 = $this->actingAs($this->headUser)->get('/Forensic_Head/personnel-roster?per_page=5&page=2', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $page2->assertStatus(200);
        $page2->assertSee('id="personnelTableMeta"', false);
    }
}

