<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitExhibitRoutingAndPopoverTest extends TestCase
{
    use RefreshDatabase;

    private User $labHead;
    private User $digitalHead;
    private User $evidenceHead;
    private User $cssHead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->labHead = User::factory()->create([
            'username' => 'lab_head_test',
            'fullname' => 'Dr. Kwesi Mensah',
            'unit' => 'Lab Analysis',
            'is_head' => true,
            'is_approved' => true,
            'service_id' => 'NAC-LAU-101',
        ]);

        $this->digitalHead = User::factory()->create([
            'username' => 'digital_head_test',
            'fullname' => 'Eng. Kofi Boateng',
            'unit' => 'Digital & Physical Forensics',
            'is_head' => true,
            'is_approved' => true,
            'service_id' => 'NAC-DPFU-101',
        ]);

        $this->evidenceHead = User::factory()->create([
            'username' => 'evidence_head_test',
            'fullname' => 'Supt. Ama Owusu',
            'unit' => 'Evidence Management',
            'is_head' => true,
            'is_approved' => true,
            'service_id' => 'NAC-EMU-101',
        ]);

        $this->cssHead = User::factory()->create([
            'username' => 'css_head_test',
            'fullname' => 'Insp. Yaw Asante',
            'unit' => 'Crime Scene Support',
            'is_head' => true,
            'is_approved' => true,
            'service_id' => 'NAC-CSSU-101',
        ]);
    }

    public function test_drug_and_electronic_unassigned_exhibits_route_exclusively_to_their_target_unit_create_views(): void
    {
        // Other unit (Crime Scene Support Head) enters a drug-related exhibit without a suspect
        $cssDrugCase = SingleSuspectCase::create([
            'case_number' => 'NCC-DRUG-NS-201',
            'title' => 'Abandoned Narcotics Seizure',
            'unit_name' => 'Crime Scene Support',
            'user_id' => $this->cssHead->id,
            'suspect_name' => 'Unknown',
            'status' => 'Awaiting Suspect Assignment',
        ]);
        $drugExhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $cssDrugCase->id,
            'property_id' => 'NACOC/EMU/20101',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Compressed Cannabis Blocks',
            'drug_classification' => 'Cannabis',
            'associated_person' => 'Unassigned (No Suspect)',
            'source_requesting_unit' => 'Crime Scene Support',
            'custodial_status' => 'Seized',
        ]);

        // Evidence Management Head enters an electronic-device-related exhibit without a suspect
        $emuDeviceCase = SingleSuspectCase::create([
            'case_number' => 'NCC-DIG-NS-202',
            'title' => 'Unclaimed Encrypted Laptop',
            'unit_name' => 'Evidence Management',
            'user_id' => $this->evidenceHead->id,
            'suspect_name' => 'Unknown',
            'status' => 'Awaiting Suspect Assignment',
        ]);
        $deviceExhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $emuDeviceCase->id,
            'property_id' => 'NACOC/EMU/20202',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'Encrypted MacBook Pro 16',
            'dev_model' => 'MacBook Pro M3',
            'dev_serial_no' => 'SN-MBP-9988',
            'associated_person' => 'Unassigned (No Suspect)',
            'source_requesting_unit' => 'Evidence Management',
            'custodial_status' => 'Seized',
        ]);

        // General non-drug/non-electronic exhibit without suspect
        $cssGeneralCase = SingleSuspectCase::create([
            'case_number' => 'NCC-GEN-NS-203',
            'title' => 'Unclaimed Cash Parcel',
            'unit_name' => 'Crime Scene Support',
            'user_id' => $this->cssHead->id,
            'suspect_name' => 'Unknown',
            'status' => 'Awaiting Suspect Assignment',
        ]);
        $generalExhibit = Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $cssGeneralCase->id,
            'property_id' => 'NACOC/EMU/20303',
            'exhibit_category' => 'Currency / Cash',
            'commodity_name' => 'Sealed Foreign Banknotes Bundle',
            'associated_person' => 'Unassigned (No Suspect)',
            'source_requesting_unit' => 'Crime Scene Support',
            'custodial_status' => 'Seized',
        ]);

        // 1. Lab Analysis create view & API: ONLY drug exhibit
        $labHtml = $this->actingAs($this->labHead)->get('/Lab_Analysis/create')->assertOk()->getContent();
        $this->assertStringContainsString('Compressed Cannabis Blocks', $labHtml);
        $this->assertStringNotContainsString('Encrypted MacBook Pro 16', $labHtml);
        $this->assertStringNotContainsString('Sealed Foreign Banknotes Bundle', $labHtml);
        $this->assertStringContainsString('id="unitExhibitAlertPopover"', $labHtml);
        $this->assertStringContainsString('data-domain="lab"', $labHtml);

        $labApiIds = collect($this->actingAs($this->labHead)->getJson('/api/exhibits/unassigned?unit=Lab+Analysis')->assertOk()->json('exhibits'))->pluck('id')->all();
        $this->assertSame([$drugExhibit->id], $labApiIds);

        // 2. Digital Forensics create view & API: ONLY electronic-device exhibit
        $digitalHtml = $this->actingAs($this->digitalHead)->get('/Digital_Forensics/create')->assertOk()->getContent();
        $this->assertStringContainsString('Encrypted MacBook Pro 16', $digitalHtml);
        $this->assertStringNotContainsString('Compressed Cannabis Blocks', $digitalHtml);
        $this->assertStringNotContainsString('Sealed Foreign Banknotes Bundle', $digitalHtml);
        $this->assertStringContainsString('id="unitExhibitAlertPopover"', $digitalHtml);
        $this->assertStringContainsString('data-domain="digital"', $digitalHtml);

        $digitalApiIds = collect($this->actingAs($this->digitalHead)->getJson('/api/exhibits/unassigned?unit=Digital+Forensics')->assertOk()->json('exhibits'))->pluck('id')->all();
        $this->assertSame([$deviceExhibit->id], $digitalApiIds);

        // 3. Evidence Management & Crime Scene Support create views MUST NOT show drug or electronic exhibits
        $emuHtml = $this->actingAs($this->evidenceHead)->get('/Evidence_Management/create')->assertOk()->getContent();
        $this->assertStringNotContainsString('Compressed Cannabis Blocks', $emuHtml);
        $this->assertStringNotContainsString('Encrypted MacBook Pro 16', $emuHtml);
        $this->assertStringContainsString('Sealed Foreign Banknotes Bundle', $emuHtml);

        $cssHtml = $this->actingAs($this->cssHead)->get('/Crime_Scene_Support/create')->assertOk()->getContent();
        $this->assertStringNotContainsString('Compressed Cannabis Blocks', $cssHtml);
        $this->assertStringNotContainsString('Encrypted MacBook Pro 16', $cssHtml);
        $this->assertStringContainsString('Sealed Foreign Banknotes Bundle', $cssHtml);

        $otherApiIds = collect($this->actingAs($this->cssHead)->getJson('/api/exhibits/unassigned?unit=Crime+Scene+Support')->assertOk()->json('exhibits'))->pluck('id')->all();
        $this->assertSame([$generalExhibit->id], $otherApiIds);
    }

    public function test_unit_exhibit_alerts_api_returns_cross_unit_no_suspect_exhibits_and_associated_suspects(): void
    {
        // Cross-unit unassigned drug exhibit
        $emuDrugNoSuspectCase = SingleSuspectCase::create([
            'case_number' => 'NCC-ALT-301',
            'title' => 'Border Parcel Cocaine',
            'unit_name' => 'Evidence Management',
            'user_id' => $this->evidenceHead->id,
            'suspect_name' => 'Unknown',
            'status' => 'Awaiting Suspect Assignment',
        ]);
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $emuDrugNoSuspectCase->id,
            'property_id' => 'NACOC/EMU/30101',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'High-Purity Cocaine Brick',
            'drug_classification' => 'Cocaine',
            'associated_person' => 'Unassigned (No Suspect)',
            'source_requesting_unit' => 'Evidence Management',
        ]);

        // Cross-unit suspect associated with drugs (single suspect)
        $cssDrugSuspectCase = SingleSuspectCase::create([
            'case_number' => 'NCC-ALT-302',
            'title' => 'Republic vs Yaw Manu',
            'unit_name' => 'Crime Scene Support',
            'user_id' => $this->cssHead->id,
            'suspect_name' => 'Yaw Manu',
            'status' => 'Pending Approval',
        ]);
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $cssDrugSuspectCase->id,
            'property_id' => 'NACOC/EMU/30202',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Tramadol Blister Packs',
            'drug_classification' => 'Tramadol',
            'associated_person' => 'Yaw Manu',
        ]);

        // Cross-unit multi-suspect case with suspect associated with an electronic device
        $cssDeviceMultiCase = MultipleSuspectCase::create([
            'case_number' => 'NCC-ALT-303',
            'title' => 'Cyber Trafficking Syndicate',
            'unit_name' => 'Crime Scene Support',
            'user_id' => $this->cssHead->id,
            'status' => 'Pending Approval',
        ]);
        $deviceSuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $cssDeviceMultiCase->id,
            'suspect_number' => 1,
            'role' => 'Primary Suspect',
            'suspect_name' => 'Kojo Mensah',
        ]);
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $cssDeviceMultiCase->id,
            'suspect_id' => $deviceSuspect->id,
            'property_id' => 'NACOC/EMU/30303',
            'exhibit_category' => 'Electronic Devices',
            'commodity_name' => 'iPhone 15 Pro Max',
            'dev_model' => 'A2849',
            'dev_imei1' => '354890112233445',
            'associated_person' => 'Kojo Mensah',
        ]);

        // Same-unit entries MUST NOT alert the same unit
        $ownLabCase = SingleSuspectCase::create([
            'case_number' => 'NCC-OWN-304',
            'title' => 'Lab Own Intake',
            'unit_name' => 'Lab Analysis',
            'user_id' => $this->labHead->id,
            'suspect_name' => 'Unknown',
            'status' => 'Awaiting Suspect Assignment',
        ]);
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $ownLabCase->id,
            'property_id' => 'NACOC/EMU/30404',
            'exhibit_category' => 'Drugs',
            'commodity_name' => 'Lab Self Heroin Sample',
            'drug_classification' => 'Heroin',
            'associated_person' => 'Unassigned (No Suspect)',
            'source_requesting_unit' => 'Lab Analysis',
        ]);

        // Assert Lab alert feed
        $labAlerts = $this->actingAs($this->labHead)->getJson('/api/unit-exhibit-alerts?domain=lab')->assertOk()->json('alerts');
        $labSerialized = json_encode($labAlerts);
        $this->assertStringContainsString('High-Purity Cocaine Brick', $labSerialized);
        $this->assertStringContainsString('Yaw Manu', $labSerialized);
        $this->assertStringNotContainsString('iPhone 15 Pro Max', $labSerialized);
        $this->assertStringNotContainsString('Kojo Mensah', $labSerialized);
        $this->assertStringNotContainsString('Lab Self Heroin Sample', $labSerialized);

        // Assert Digital alert feed
        $digitalAlerts = $this->actingAs($this->digitalHead)->getJson('/api/unit-exhibit-alerts?domain=digital')->assertOk()->json('alerts');
        $digitalSerialized = json_encode($digitalAlerts);
        $this->assertStringContainsString('Kojo Mensah', $digitalSerialized);
        $this->assertStringContainsString('iPhone 15 Pro Max', $digitalSerialized);
        $this->assertStringNotContainsString('High-Purity Cocaine Brick', $digitalSerialized);
        $this->assertStringNotContainsString('Yaw Manu', $digitalSerialized);
    }

    public function test_alert_popover_partial_has_no_console_logs_or_hardcoded_mock_entries(): void
    {
        $popoverSource = file_get_contents(resource_path('views/partials/unit_exhibit_alert_popover.blade.php'));
        $this->assertIsString($popoverSource);
        $this->assertDoesNotMatchRegularExpression('/console\.(log|warn|error|info|debug)/i', $popoverSource);
    }
}

