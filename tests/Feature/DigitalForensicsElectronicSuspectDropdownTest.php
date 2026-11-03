<?php

namespace Tests\Feature;

use App\Models\CaseSuspect;
use App\Models\DpfReport;
use App\Models\Exhibit;
use App\Models\MultipleSuspectCase;
use App\Models\SingleSuspectCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalForensicsElectronicSuspectDropdownTest extends TestCase
{
    use RefreshDatabase;

    private User $examiner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->examiner = User::factory()->create([
            'unit' => config('forensics.units.DPFU.name') ?? 'Digital & Physical Forensics',
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function single_suspect_case_with_electronic_exhibit_appears_in_dropdown(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'REF-ELEC-001',
            'title' => 'Single Electronic Investigation',
            'suspect_name' => 'Kwadwo Digital',
            'suspect_alias' => 'ByteMaster',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Apple iPhone 15 Pro Max',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('REF-ELEC-001 — Kwadwo Digital (ByteMaster)', $html);
    }

    #[Test]
    public function single_suspect_case_with_drug_exhibit_does_not_appear_in_dropdown(): void
    {
        $drugCat = config('forensic.exhibit_categories.drugs', 'Drugs');

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'REF-DRUG-001',
            'title' => 'Pure Drug Case',
            'suspect_name' => 'Yaw Narcotics',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => $drugCat,
            'dev_model' => 'White Powder Substance',
            'seizure_quantity' => '5',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringNotContainsString('REF-DRUG-001', $html);
        $this->assertStringNotContainsString('Yaw Narcotics', $html);
    }

    #[Test]
    public function multi_suspect_mixed_case_shows_only_suspect_with_electronic_exhibit(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');
        $drugCat = config('forensic.exhibit_categories.drugs', 'Drugs');

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'REF-MIXED-001',
            'title' => 'Operation Mixed Border',
            'status' => 'Approved',
            'total_suspects_count' => 2,
        ]);

        $suspectA = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Suspect Alpha',
            'role' => 'Primary Suspect',
        ]);

        $suspectB = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Suspect Beta',
            'role' => 'Co-Suspect',
        ]);

        // Suspect A is associated with Drugs
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectA->id,
            'exhibit_category' => $drugCat,
            'dev_model' => 'Heroin Block',
            'seizure_quantity' => '2',
        ]);

        // Suspect B is associated with Electronic Devices
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectB->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Samsung Galaxy S24 Ultra',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Suspect B MUST be present
        $this->assertStringContainsString('Suspect Beta', $html);
        $this->assertStringContainsString('REF-MIXED-001|' . $suspectB->id, $html);

        // Suspect A MUST NOT be present in dropdown options
        $this->assertStringNotContainsString('Suspect Alpha', $html);
        $this->assertStringNotContainsString('REF-MIXED-001|' . $suspectA->id, $html);
    }

    #[Test]
    public function multi_suspect_case_where_multiple_suspects_have_electronic_exhibits_shows_each_as_separate_option(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'REF-MULTI-ELEC',
            'title' => 'Operation Cyber Ring',
            'status' => 'Approved',
            'total_suspects_count' => 2,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Amina Cyber',
            'role' => 'Primary Suspect',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Kojo Hacker',
            'role' => 'Co-Suspect',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect1->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Dell XPS 15 Laptop',
            'seizure_quantity' => '1',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect2->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Apple iPad Pro',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Both suspects must appear as distinct options
        $this->assertStringContainsString('REF-MULTI-ELEC|' . $suspect1->id, $html);
        $this->assertStringContainsString('Amina Cyber', $html);

        $this->assertStringContainsString('REF-MULTI-ELEC|' . $suspect2->id, $html);
        $this->assertStringContainsString('Kojo Hacker', $html);
    }

    #[Test]
    public function multi_suspect_case_with_no_electronic_suspects_shows_neither(): void
    {
        $drugCat = config('forensic.exhibit_categories.drugs', 'Drugs');
        $weaponCat = config('forensic.exhibit_categories.weapons', 'Weapons');

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'REF-NO-ELEC',
            'title' => 'Weapons and Drugs Operation',
            'status' => 'Approved',
            'total_suspects_count' => 2,
        ]);

        $suspect1 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Armed Suspect One',
        ]);

        $suspect2 = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Drug Courier Two',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect1->id,
            'exhibit_category' => $weaponCat,
            'dev_model' => 'Glock 19 Pistol',
            'seizure_quantity' => '1',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect2->id,
            'exhibit_category' => $drugCat,
            'dev_model' => 'Cocaine Brick',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringNotContainsString('REF-NO-ELEC', $html);
        $this->assertStringNotContainsString('Armed Suspect One', $html);
        $this->assertStringNotContainsString('Drug Courier Two', $html);
    }

    #[Test]
    public function suspect_with_multiple_electronic_exhibits_appears_only_once(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'REF-DEDUP-001',
            'title' => 'Multiple Devices Single Suspect',
            'status' => 'Approved',
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Solo Tech Suspect',
        ]);

        // 3 separate electronic exhibits for this suspect
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'iPhone 14',
            'seizure_quantity' => '1',
        ]);
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'SanDisk 1TB SSD',
            'seizure_quantity' => '1',
        ]);
        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Huawei 5G Router',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Option value should appear exactly once in the HTML
        $targetValue = 'value="' . 'REF-DEDUP-001|' . $suspect->id . '"';
        $this->assertEquals(1, substr_count($html, $targetValue));
    }

    #[Test]
    public function placeholder_option_is_always_present_as_the_first_option(): void
    {
        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('<option value="">— Select Digital Case Reference —</option>', $html);
    }

    #[Test]
    public function empty_state_option_is_rendered_when_no_electronic_suspects_exist(): void
    {
        $drugCat = config('forensic.exhibit_categories.drugs', 'Drugs');

        // Create only a drug case
        $singleCase = SingleSuspectCase::create([
            'case_number' => 'REF-EMPTY-STATE',
            'suspect_name' => 'Drug Only Suspect',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => $drugCat,
            'commodity_name' => 'Methamphetamine',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('No electronic-devices suspects available for this selection', $html);
    }

    #[Test]
    public function case_category_matching_is_case_insensitive_and_respects_config(): void
    {
        // Lowercase variant in DB
        $singleCase = SingleSuspectCase::create([
            'case_number' => 'REF-LOWER-ELEC',
            'suspect_name' => 'Lowercase Exhibit Suspect',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => 'electronic devices', // lowercase
            'dev_model' => 'Smart Watch',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('REF-LOWER-ELEC', $html);
        $this->assertStringContainsString('Lowercase Exhibit Suspect', $html);
    }

    #[Test]
    public function submitting_composite_case_number_stores_clean_case_number_and_suspect_id(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'REF-POST-001',
            'status' => 'active',
            'total_suspects_count' => 1,
        ]);

        $suspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Post Suspect Target',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspect->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Target iPhone',
            'seizure_quantity' => '1',
        ]);

        $compositeVal = 'REF-POST-001|' . $suspect->id;

        $response = $this->actingAs($this->examiner)->post('/Digital_Forensics/forensic-status', [
            'case_number' => $compositeVal,
            'location_post' => 'Kotoka International Airport',
            'devices_retrieved' => 'Target iPhone',
            'quantity' => '1',
            'court_order_requested' => 'NO',
            'court_order_granted' => 'NO',
            'extraction_analysis_conducted' => 'NO',
            'report_witness_statement_sent' => 'NO',
        ]);

        $response->assertStatus(200);

        $savedReport = DpfReport::where('case_number', 'REF-POST-001')->latest('id')->first();
        $this->assertNotNull($savedReport);
        $this->assertEquals('REF-POST-001', $savedReport->case_number);
        $this->assertEquals($suspect->id, $savedReport->suspect_id);
    }

    #[Test]
    public function directory_table_shows_single_suspect_case_with_electronic_exhibit(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'DIR-ELEC-001',
            'title' => 'Digital Case Directory Test',
            'suspect_name' => 'Akosua Digital',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Apple MacBook M3 Pro',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        preg_match('/<table[^>]*id="forensicDirectoryTable"[^>]*>(.*?)<\/table>/s', $html, $matches);
        $tableHtml = $matches[1] ?? '';

        $this->assertStringContainsString('DIR-ELEC-001', $tableHtml);
        $this->assertStringContainsString('Akosua Digital', $tableHtml);
    }

    #[Test]
    public function directory_table_excludes_pure_drug_and_weapon_cases(): void
    {
        $drugCat = config('forensic.exhibit_categories.drugs', 'Drugs');
        $weaponCat = config('forensic.exhibit_categories.weapons', 'Weapons');

        $drugCase = SingleSuspectCase::create([
            'case_number' => 'DIR-DRUG-999',
            'title' => 'Pure Cocaine Case',
            'suspect_name' => 'Kojo Narcotics',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $drugCase->id,
            'exhibit_category' => $drugCat,
            'dev_model' => 'Cocaine Brick',
            'seizure_quantity' => '2',
        ]);

        $weaponCase = SingleSuspectCase::create([
            'case_number' => 'DIR-WEAPON-999',
            'title' => 'Pure Firearm Case',
            'suspect_name' => 'Kofi Gunman',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $weaponCase->id,
            'exhibit_category' => $weaponCat,
            'dev_model' => 'AK-47 Rifle',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        preg_match('/<table[^>]*id="forensicDirectoryTable"[^>]*>(.*?)<\/table>/s', $html, $matches);
        $tableHtml = $matches[1] ?? '';

        $this->assertStringNotContainsString('DIR-DRUG-999', $tableHtml);
        $this->assertStringNotContainsString('Kojo Narcotics', $tableHtml);
        $this->assertStringNotContainsString('DIR-WEAPON-999', $tableHtml);
        $this->assertStringNotContainsString('Kofi Gunman', $tableHtml);
    }

    #[Test]
    public function directory_table_in_multi_suspect_mixed_case_shows_electronic_suspect_not_drug_suspect(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');
        $drugCat = config('forensic.exhibit_categories.drugs', 'Drugs');

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'DIR-MIXED-001',
            'title' => 'Operation Mixed Border',
            'status' => 'Approved',
            'total_suspects_count' => 2,
        ]);

        $drugSuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 1,
            'suspect_name' => 'Drug Lord Alpha',
            'role' => 'Primary Suspect',
        ]);

        $electronicSuspect = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_number' => 2,
            'suspect_name' => 'Hacker Beta',
            'role' => 'Co-Suspect',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $drugSuspect->id,
            'exhibit_category' => $drugCat,
            'dev_model' => 'Heroin Parcel',
            'seizure_quantity' => '3',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $electronicSuspect->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Encrypted iPhone 15',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        preg_match('/<table[^>]*id="forensicDirectoryTable"[^>]*>(.*?)<\/table>/s', $html, $matches);
        $tableHtml = $matches[1] ?? '';

        // Case must appear in table
        $this->assertStringContainsString('DIR-MIXED-001', $tableHtml);

        // Electronic suspect MUST appear in the directory table row
        $this->assertStringContainsString('Hacker Beta', $tableHtml);

        // Drug suspect MUST NOT appear in the directory table row
        $this->assertStringNotContainsString('Drug Lord Alpha', $tableHtml);
    }

    #[Test]
    public function directory_row_count_badge_counts_only_electronic_cases(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');
        $drugCat = config('forensic.exhibit_categories.drugs', 'Drugs');

        // Electronic case 1
        $elecCase1 = SingleSuspectCase::create([
            'case_number' => 'BADGE-ELEC-001',
            'suspect_name' => 'Badge Elec One',
            'status' => 'Approved',
        ]);
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $elecCase1->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Smartphone 1',
        ]);

        // Electronic case 2
        $elecCase2 = SingleSuspectCase::create([
            'case_number' => 'BADGE-ELEC-002',
            'suspect_name' => 'Badge Elec Two',
            'status' => 'Approved',
        ]);
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $elecCase2->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Smartphone 2',
        ]);

        // Pure drug case (should NOT be counted)
        $drugCase = SingleSuspectCase::create([
            'case_number' => 'BADGE-DRUG-001',
            'suspect_name' => 'Badge Drug One',
            'status' => 'Approved',
        ]);
        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $drugCase->id,
            'exhibit_category' => $drugCat,
            'dev_model' => 'Cannabis Sativa',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        preg_match('/<span[^>]*id="directoryRowCountBadge"[^>]*>(.*?)<\/span>/s', $html, $matches);
        $badgeText = trim($matches[1] ?? '');

        // Badge should indicate Total: 2 (only the 2 electronic cases)
        $this->assertStringContainsString('Total: 2', $badgeText);
        $this->assertStringNotContainsString('Total: 3', $badgeText);
    }

    #[Test]
    public function directory_table_excludes_saved_reports_belonging_to_pure_drug_cases(): void
    {
        $drugCat = config('forensic.exhibit_categories.drugs', 'Drugs');

        $drugCase = SingleSuspectCase::create([
            'case_number' => 'DIR-SAVED-DRUG-001',
            'title' => 'Pure Drug Case with Legacy Saved Report',
            'suspect_name' => 'Old Drug Suspect',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $drugCase->id,
            'exhibit_category' => $drugCat,
            'dev_model' => 'Heroin Powder',
        ]);

        // Report saved for drug case
        DpfReport::create([
            'case_number' => 'DIR-SAVED-DRUG-001',
            'single_suspect_case_id' => $drugCase->id,
            'user_id' => $this->examiner->id,
            'devices_retrieved' => 'Heroin Powder',
            'quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();
        preg_match('/<table[^>]*id="forensicDirectoryTable"[^>]*>(.*?)<\/table>/s', $html, $matches);
        $tableHtml = $matches[1] ?? '';

        $this->assertStringNotContainsString('DIR-SAVED-DRUG-001', $tableHtml);
        $this->assertStringNotContainsString('Old Drug Suspect', $tableHtml);
    }

    #[Test]
    public function saved_court_order_documents_are_present_in_case_map_data_and_rendered_when_saved(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'REF-COURT-001',
            'title' => 'Case with Saved Court Order Document',
            'suspect_name' => 'Kojo High Court',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Samsung Galaxy S24 Ultra',
            'seizure_quantity' => '1',
        ]);

        $coFilePath = 'uploads/forensic_documents/1792908002_6add9ae217861_court_order_High_Court_Order_Warrant.pdf';

        DpfReport::create([
            'case_number' => 'REF-COURT-001',
            'single_suspect_case_id' => $singleCase->id,
            'user_id' => $this->examiner->id,
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'court_order_file' => json_encode([$coFilePath]),
            'devices_retrieved' => 'Samsung Galaxy S24 Ultra',
            'quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Check caseMapData contains court_order_files with clean file name
        $this->assertStringContainsString('High_Court_Order_Warrant.pdf', $html);
        $this->assertStringContainsString('court_order_files', $html);

        // 2. Check the saved court order wrapper and file link exist in the response
        $this->assertStringContainsString('Saved Court Order Documents', $html);
        $this->assertStringContainsString('courtOrderSavedFilesWrapper', $html);
        $this->assertStringContainsString('courtOrderSavedFilesList', $html);
        $this->assertStringContainsString('View', $html);
    }

    #[Test]
    public function case_without_saved_court_order_documents_has_empty_files_and_hidden_saved_wrapper(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'REF-NO-COURT-001',
            'title' => 'Case without Court Order',
            'suspect_name' => 'Akosua Clean',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'MacBook Pro M3',
            'seizure_quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Saved files wrapper is hidden by default when no court order files exist
        $this->assertStringContainsString('id="courtOrderSavedFilesWrapper" class="hidden', $html);
    }

    #[Test]
    public function multi_suspect_case_isolates_court_order_files_by_suspect(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'REF-MULTI-COURT-001',
            'title' => 'Multi Suspect Court Order Test',
            'status' => 'Approved',
        ]);

        $suspectWithOrder = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Suspect Granted Order',
            'suspect_number' => 'SUSP-001',
        ]);

        $suspectWithoutOrder = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Suspect No Order',
            'suspect_number' => 'SUSP-002',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectWithOrder->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'iPhone 14',
            'seizure_quantity' => '1',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectWithoutOrder->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Dell Laptop',
            'seizure_quantity' => '1',
        ]);

        $filePath = 'uploads/forensic_documents/1792908002_6add9ae217861_court_order_Suspect1_Order.pdf';

        DpfReport::create([
            'case_number' => 'REF-MULTI-COURT-001',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectWithOrder->id,
            'user_id' => $this->examiner->id,
            'court_order_requested' => 'YES',
            'court_order_granted' => 'YES',
            'court_order_file' => json_encode([$filePath]),
            'devices_retrieved' => 'iPhone 14',
            'quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Check that suspect 1's composite key in caseMapData includes the court order file
        $keySuspect1 = 'REF-MULTI-COURT-001|' . $suspectWithOrder->id;
        $keySuspect2 = 'REF-MULTI-COURT-001|' . $suspectWithoutOrder->id;

        $this->assertStringContainsString($keySuspect1, $html);
        $this->assertStringContainsString($keySuspect2, $html);
        $this->assertStringContainsString('Suspect1_Order.pdf', $html);
    }

    #[Test]
    public function saved_report_witness_statement_documents_are_present_in_case_map_data_and_rendered_when_saved(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $singleCase = SingleSuspectCase::create([
            'case_number' => 'REF-RW-SAVED-001',
            'suspect_name' => 'Report Suspect',
            'status' => 'Approved',
        ]);

        Exhibit::create([
            'case_type' => 'SINGLE',
            'single_suspect_case_id' => $singleCase->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Samsung S22',
            'seizure_quantity' => '1',
        ]);

        $filePath = 'uploads/forensic_documents/1792908002_6add9ae217861_report_witness_Final_Forensic_Report.pdf';

        DpfReport::create([
            'case_number' => 'REF-RW-SAVED-001',
            'single_suspect_case_id' => $singleCase->id,
            'user_id' => $this->examiner->id,
            'report_witness_statement_sent' => 'YES',
            'report_witness_statement_message' => 'Full analytical report transmitted to investigations',
            'report_witness_statement_file' => json_encode([$filePath]),
            'devices_retrieved' => 'Samsung S22',
            'quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Check that caseMapData contains report_witness_files with clean document name
        $this->assertStringContainsString('report_witness_files', $html);
        $this->assertStringContainsString('Final_Forensic_Report.pdf', $html);

        // Check that the saved report & witness statement files wrapper is visible in initial render
        $this->assertStringContainsString('id="reportWitnessSavedFilesWrapper"', $html);
        $this->assertStringContainsString('Saved Report &amp; Statement Documents', $html);
        $this->assertStringContainsString('1 SAVED', $html);
    }

    #[Test]
    public function saved_report_witness_statement_documents_multi_suspect_isolation(): void
    {
        $electronicCat = config('forensic.exhibit_categories.electronic_devices', 'Electronic Devices');

        $multiCase = MultipleSuspectCase::create([
            'case_number' => 'REF-MULTI-RW-001',
            'title' => 'Multi Suspect Report Witness Test',
            'status' => 'Approved',
        ]);

        $suspectWithReport = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Suspect With Report',
            'suspect_number' => 'SUSP-RW-001',
        ]);

        $suspectWithoutReport = CaseSuspect::create([
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_name' => 'Suspect Without Report',
            'suspect_number' => 'SUSP-RW-002',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectWithReport->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Google Pixel 7',
            'seizure_quantity' => '1',
        ]);

        Exhibit::create([
            'case_type' => 'MULTIPLE',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectWithoutReport->id,
            'exhibit_category' => $electronicCat,
            'dev_model' => 'Motorola Edge',
            'seizure_quantity' => '1',
        ]);

        $filePath = 'uploads/forensic_documents/1792908002_6add9ae217861_report_witness_Suspect1_Statement.docx';

        DpfReport::create([
            'case_number' => 'REF-MULTI-RW-001',
            'multiple_suspect_case_id' => $multiCase->id,
            'suspect_id' => $suspectWithReport->id,
            'user_id' => $this->examiner->id,
            'report_witness_statement_sent' => 'YES',
            'report_witness_statement_message' => 'Witness statement sent',
            'report_witness_statement_file' => json_encode([$filePath]),
            'devices_retrieved' => 'Google Pixel 7',
            'quantity' => '1',
        ]);

        $response = $this->actingAs($this->examiner)->get('/Digital_Forensics/forensic-status');
        $response->assertStatus(200);

        $html = $response->getContent();

        $keySuspect1 = 'REF-MULTI-RW-001|' . $suspectWithReport->id;
        $keySuspect2 = 'REF-MULTI-RW-001|' . $suspectWithoutReport->id;

        $this->assertStringContainsString($keySuspect1, $html);
        $this->assertStringContainsString($keySuspect2, $html);
        $this->assertStringContainsString('Suspect1_Statement.docx', $html);
    }
}


